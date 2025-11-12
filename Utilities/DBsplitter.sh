#!/bin/bash
# ============================================================
# Script to extract data for a specific client
# from a MariaDB database and create a new database just
# for that client, with confirmation before overwriting
# existing databases.
# Also copies the user permissions from the original database
# ============================================================

# === PARAMETER VALIDATION ===
if [ $# -ne 3 ]; then
  echo "Usage: $0 <ORIGINAL_DB> <USER> <CLIENT_ID>"
  echo "Example: $0 my_db root 42"
  exit 1
fi

DB_ORIGINAL="$1"
DB_USER="$2"
CLIENT_ID="$3"
NEW_DB="client_${CLIENT_ID}"

# Prompt for password securely
read -s -p "Enter MySQL password for user ${DB_USER}: " DB_PASS
echo

MYSQL="mysql -u${DB_USER} -p${DB_PASS} -N -B"

echo "=== Extracting client ${CLIENT_ID} from database ${DB_ORIGINAL} ==="

# Check if the target database already exists
EXISTS=$($MYSQL -e "SELECT COUNT(*) FROM information_schema.schemata WHERE schema_name='${NEW_DB}';")
if [ "$EXISTS" -ne 0 ]; then
  read -p "Database ${NEW_DB} already exists. Do you want to drop it and continue? (y/n) " RESP
  if [[ "$RESP" != [yY] ]]; then
    echo "Operation cancelled by user."
    exit 1
  fi
fi

# Create the new database
$MYSQL -e "DROP DATABASE IF EXISTS ${NEW_DB}; CREATE DATABASE ${NEW_DB};"

# Get all tables
TABLES=$($MYSQL -e "SHOW TABLES FROM ${DB_ORIGINAL};")

# Iterate over all tables
for TABLE in $TABLES; do
  echo "  -> Processing table ${TABLE}..."

  # Create table structure
  $MYSQL -e "CREATE TABLE ${NEW_DB}.${TABLE} LIKE ${DB_ORIGINAL}.${TABLE};"

  # Special treatment for RS_CLIENTS
  if [ "$TABLE" == "RS_CLIENTS" ]; then
    echo "     Table RS_CLIENTS -> copying only record for client ${CLIENT_ID}"
    $MYSQL -e "
      INSERT INTO ${NEW_DB}.RS_CLIENTS
      SELECT * FROM ${DB_ORIGINAL}.RS_CLIENTS
      WHERE RS_ID=${CLIENT_ID};
    "
    continue
  fi

  # Check if the table has the RS_CLIENT_ID column
  HAS_COL=$($MYSQL -e "
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema='${DB_ORIGINAL}'
      AND table_name='${TABLE}'
      AND column_name='RS_CLIENT_ID';
  ")

  if [ "$HAS_COL" -eq 1 ]; then
    echo "     Contains RS_CLIENT_ID -> copying data for client ${CLIENT_ID}"
    $MYSQL -e "
      INSERT INTO ${NEW_DB}.${TABLE}
      SELECT * FROM ${DB_ORIGINAL}.${TABLE}
      WHERE RS_CLIENT_ID=${CLIENT_ID};
    "
  else
    echo "     Does not contain RS_CLIENT_ID -> copying entire table"
    $MYSQL -e "
      INSERT INTO ${NEW_DB}.${TABLE}
      SELECT * FROM ${DB_ORIGINAL}.${TABLE};
    "
  fi
done

# === Copy user permissions from original database to new database ===
echo "=== Copying user privileges ==="
USERS=$($MYSQL -e "SELECT DISTINCT GRANTEE FROM information_schema.SCHEMA_PRIVILEGES WHERE TABLE_SCHEMA='${DB_ORIGINAL}';" | sed "s/'//g" | sed "s/@/ /")
for U in $USERS; do
  USER=$(echo $U | awk '{print $1}')
  HOST=$(echo $U | awk '{print $2}')
  echo "  -> Copying privileges for ${USER}@${HOST}"
  PRIVS=$($MYSQL -e "SHOW GRANTS FOR '${USER}'@'${HOST}';" | grep "ON \`${DB_ORIGINAL}\`.*" | sed "s/\`${DB_ORIGINAL}\`/\`${NEW_DB}\`/g")
  for P in $PRIVS; do
    $MYSQL -e "$P;"
  done
done
$MYSQL -e "FLUSH PRIVILEGES;"

echo
echo ">>> Client ${CLIENT_ID} successfully exported to database ${NEW_DB} with privileges"
echo "=== Process completed ==="
