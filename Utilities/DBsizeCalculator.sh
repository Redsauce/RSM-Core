#!/bin/bash
# Usage: ./calculate_db_data_bytes_all_verbose.sh <db_name> <user>
# The password will be requested securely

if [ "$#" -ne 2 ]; then
    echo "Usage: $0 <db_name> <user>"
    echo "Example: $0 your_db_name my_user"
    exit 1
fi

DB_NAME="$1"
DB_USER="$2"

read -s -p "Password for $DB_USER: " DB_PASS
echo

MYSQL="mysql -u $DB_USER -p$DB_PASS $DB_NAME -N -B"

echo "Getting list of clients..."
# Read line by line using tab as separator
$MYSQL -e "SELECT RS_ID, RS_NAME FROM rs_clients;" | while IFS=$'\t' read -r client_id client_name; do

    # ASCII box for client
    header=" Client $client_id: $client_name "
    border=$(printf '%*s' "${#header}" '' | tr ' ' '=')
    echo
    echo "$border"
    echo "$header"
    echo "$border"

    total_bytes=0

    # Get all tables with RS_CLIENT_ID
    tables=$($MYSQL -e "SELECT DISTINCT TABLE_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='$DB_NAME' AND COLUMN_NAME='RS_CLIENT_ID';")

    for table in $tables; do
        # Text/JSON/BLOB columns
        text_cols=$($MYSQL -e "
            SELECT COLUMN_NAME
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA='$DB_NAME' AND TABLE_NAME='$table'
              AND COLUMN_NAME<>'RS_CLIENT_ID'
              AND DATA_TYPE IN ('char','varchar','text','tinytext','mediumtext','longtext',
                                'blob','tinyblob','mediumblob','longblob','json','variant');")

        sum_text=""
        for col in $text_cols; do
            if [ -z "$sum_text" ]; then
                sum_text="IFNULL(LENGTH(\`$col\`),0)"
            else
                sum_text="$sum_text + IFNULL(LENGTH(\`$col\`),0)"
            fi
        done

        # Numeric/date columns
        num_bytes=0
        num_cols=$($MYSQL -e "
            SELECT DATA_TYPE
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA='$DB_NAME' AND TABLE_NAME='$table'
              AND COLUMN_NAME<>'RS_CLIENT_ID'
              AND DATA_TYPE IN ('tinyint','smallint','mediumint','int','bigint','float','decimal','double','date','datetime','timestamp');")

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

        # Build query safely
        if [ -n "$sum_text" ]; then
            query="SELECT IFNULL(SUM($sum_text),0) + COUNT(*)*$num_bytes FROM \`$table\` WHERE RS_CLIENT_ID=$client_id;"
        else
            query="SELECT COUNT(*)*$num_bytes FROM \`$table\` WHERE RS_CLIENT_ID=$client_id;"
        fi

        bytes=$($MYSQL -e "$query")
        total_bytes=$((total_bytes + bytes))

        # Show table and bytes
        echo "  Table: $table: $bytes Bytes"
    done

    # Update rs_client_stats table
    $MYSQL -e "
    INSERT INTO rs_client_stats (RS_CLIENT_ID, STAT_DATE, DB_DATA_BYTES)
    VALUES ($client_id, CURDATE(), $total_bytes)
    ON DUPLICATE KEY UPDATE DB_DATA_BYTES=VALUES(DB_DATA_BYTES);"

    echo "  Total bytes for client $client_id: $total_bytes"
done

echo
echo "------------------------------"
echo "Calculation completed for all clients."
echo "------------------------------"