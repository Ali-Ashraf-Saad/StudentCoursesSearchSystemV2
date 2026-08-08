#!/bin/bash
set -e

# شغّل السكربت مع السنة والترم: ./codePyData/RUN.sh 2025_2026_1
DATA_YEAR="${1:-}"
if [[ ! "$DATA_YEAR" =~ ^[0-9]{4}_[0-9]{4}_[12]$ ]]; then
  echo "السنة يجب أن تكون بصيغة YYYY_YYYY_1 أو YYYY_YYYY_2" >&2
  exit 1
fi
export DATA_YEAR

SOURCE_DIR="txtData/$DATA_YEAR"
if [[ ! -d "$SOURCE_DIR" ]]; then
  echo "مجلد البيانات غير موجود: $SOURCE_DIR" >&2
  exit 1
fi

for department in CS IT IS gen; do
  if [[ ! -f "$SOURCE_DIR/$department.txt" ]]; then
    echo "الملف غير موجود: $SOURCE_DIR/$department.txt" >&2
    exit 1
  fi
done

if [[ ! -f "$SOURCE_DIR/schedule.py" ]]; then
  echo "ملف الجداول غير موجود: $SOURCE_DIR/schedule.py" >&2
  exit 1
fi

rm -rf "data/$DATA_YEAR" "foldersData/$DATA_YEAR"
rm -f "$SOURCE_DIR/All.txt" "$SOURCE_DIR/rooms.txt" "$SOURCE_DIR/subjectsName.txt"
# from CS.txt gen.txt IS.txt IT.txt
python3 -u "codePyData/AllCode.py" # generate "$SOURCE_DIR/All.txt"
python3 -u "codePyData/rooms.py" # generate "$SOURCE_DIR/rooms.txt"
python3 -u "codePyData/subjectName.py" # generate "$SOURCE_DIR/subjectsName.txt"
python3 -u "codePyData/foldersOutput.py" # generate "foldersData/$DATA_YEAR"

#from "foldersData/$DATA_YEAR"
python3 -u "codePyData/jsonOutput.py" # generate "data/$DATA_YEAR" JSON output
