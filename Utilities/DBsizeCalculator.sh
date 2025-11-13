#!/bin/bash
# Usage: ./calc_all_dbs_verbose.sh [<DBNAME> <DBUSERNAME>]
# If no parameters, uses DBUSERNAME and DBPASSWORD from environment and processes all databases
# If DBNAME is specified, only that database is processed

# Function to decode HTML entities
decode_html() {
    echo "$1" | python3 -c "import sys, html; print(html.unescape(sys.stdin.read().strip()))"
}

# Check environment variables or parameters
DB_NAME=${RSMDBNAME:-$1}
DB_USER=${RSMDBUSERNAME:-$2}
DB_PASS=${RSMDBPASSWORD}

# Validate user is provided
if [ -z "$DB_USER" ]; then
    echo "Usage: $0 [<DBNAME> <DBUSERNAME>]"
    echo "Or set environment variables: DBUSERNAME, DBPASSWORD (and optionally DBNAME)"
    echo "If DBNAME is not specified, all databases will be processed"
    exit 1
fi

# Ask for password if not in environment
if [ -z "$DB_PASS" ]; then
    read -s -p "Password for $DB_USER: " DB_PASS
    echo
fi

MYSQL="mysql -u$DB_USER -p$DB_PASS -N -B"

# Check connection
if ! $MYSQL -e "SELECT 1;" > /dev/null 2>&1; then
    echo "❌ Unable to connect to MySQL. Check credentials."
    exit 1
fi

# Determine which databases to process
if [ -z "$DB_NAME" ]; then
    echo "No database specified. Processing all available databases..."
    DBS=$($MYSQL -e "SHOW DATABASES;" | grep -vE "information_schema|mysql|performance_schema|sys")
else
    echo "Processing specific database: $DB_NAME"
    DBS="$DB_NAME"
fi

for DB in $DBS; do
    echo
    echo "==============================="
    echo "   Processing database: $DB"
    echo "==============================="

    MYSQL_DB="$MYSQL $DB"

    echo "Getting clients..."
    $MYSQL_DB -e "SELECT RS_ID, RS_NAME FROM rs_clients;" | while IFS=$'\t' read -r client_id client_name; do
        # Decode HTML entities in client name
        client_name_decoded=$(decode_html "$client_name")
        
        header="  Client $client_id: $client_name_decoded  "
        border=$(printf '%*s' "${#header}" '' | tr ' ' '=')
        echo
        echo "$border"
        echo "$header"
        echo "$border"

        total_bytes=0

        tables=$($MYSQL_DB -e "
            SELECT DISTINCT TABLE_NAME
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA='$DB'
              AND COLUMN_NAME='RS_CLIENT_ID';
        ")

        for table in $tables; do
            # Find textual columns
            text_cols=$($MYSQL_DB -e "
                SELECT COLUMN_NAME
                FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA='$DB'
                  AND TABLE_NAME='$table'
                  AND COLUMN_NAME<>'RS_CLIENT_ID'
                  AND DATA_TYPE IN ('char','varchar','text','tinytext','mediumtext','longtext',
                                    'blob','tinyblob','mediumblob','longblob','json','variant');
            ")

            sum_text=""
            for col in $text_cols; do
                if [ -z "$sum_text" ]; then
                    sum_text="COALESCE(LENGTH(\`$col\`),0)"
                else
                    sum_text="$sum_text + COALESCE(LENGTH(\`$col\`),0)"
                fi
            done

            # Numeric and date columns
            num_bytes=0
            num_cols=$($MYSQL_DB -e "
                SELECT DATA_TYPE
                FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA='$DB'
                  AND TABLE_NAME='$table'
                  AND COLUMN_NAME<>'RS_CLIENT_ID'
                  AND DATA_TYPE IN ('tinyint','smallint','mediumint','int','bigint','float','decimal','double','date','datetime','timestamp');
            ")

            for dtype in $num_cols; do
                case "$dtype" in
                    tinyint) size=1 ;;
                    smallint) size=2 ;;
                    mediumint) size=3 ;;
                    int) size=4 ;;
                    bigint) size=8 ;;
                    float|decimal|double|date|datetime|timestamp) size=8 ;;
                    *) size=0 ;;
                esac
                num_bytes=$((num_bytes + size))
            done

            if [ -n "$sum_text" ]; then
                query="SELECT COALESCE(SUM($sum_text), 0) + COUNT(*)*$num_bytes FROM \`$table\` WHERE RS_CLIENT_ID=$client_id;"
            else
                query="SELECT COUNT(*)*$num_bytes FROM \`$table\` WHERE RS_CLIENT_ID=$client_id;"
            fi

            bytes=$($MYSQL_DB -e "$query")
            bytes=${bytes:-0}

            total_bytes=$((total_bytes + bytes))
            echo "   Table: $table: $bytes Bytes"
        done

        # Insert/update rs_client_stats
        $MYSQL_DB -e "
            INSERT INTO rs_client_stats (RS_CLIENT_ID, STAT_DATE, DB_DATA_BYTES)
            VALUES ($client_id, CURDATE(), $total_bytes)
            ON DUPLICATE KEY UPDATE DB_DATA_BYTES=VALUES(DB_DATA_BYTES);
        "

        # Also compute files and images
        $MYSQL_DB -e "
            INSERT INTO rs_client_stats (RS_CLIENT_ID, STAT_DATE, DB_FILES_BYTES, DB_IMAGES_BYTES)
            SELECT c.RS_ID, CURDATE(),
                   IFNULL(f_total.DB_FILES_BYTES, 0),
                   IFNULL(i_total.DB_IMAGES_BYTES, 0)
            FROM rs_clients c
            LEFT JOIN (
                SELECT RS_CLIENT_ID, SUM(RS_SIZE) AS DB_FILES_BYTES
                FROM rs_property_files
                GROUP BY RS_CLIENT_ID
            ) AS f_total ON f_total.RS_CLIENT_ID = c.RS_ID
            LEFT JOIN (
                SELECT RS_CLIENT_ID, SUM(RS_SIZE) AS DB_IMAGES_BYTES
                FROM rs_property_images
                GROUP BY RS_CLIENT_ID
            ) AS i_total ON i_total.RS_CLIENT_ID = c.RS_ID
            ON DUPLICATE KEY UPDATE
                DB_FILES_BYTES = VALUES(DB_FILES_BYTES),
                DB_IMAGES_BYTES = VALUES(DB_IMAGES_BYTES);
        "

        echo "   Total bytes for client $client_id: $total_bytes"
    done
done

echo
echo "------------------------------"
echo "Calculation completed for all clients and databases."