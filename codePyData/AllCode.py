import os
import re

DATA_YEAR = os.environ.get("DATA_YEAR", "").strip()
if not re.fullmatch(r"\d{4}_\d{4}_[12]", DATA_YEAR):
    raise SystemExit("DATA_YEAR يجب أن يكون بصيغة YYYY_YYYY_1 أو YYYY_YYYY_2")

source_dir = os.path.join("txtData", DATA_YEAR)
files = [os.path.join(source_dir, name) for name in ("CS.txt", "IT.txt", "IS.txt", "gen.txt")]

with open(os.path.join(source_dir, "All.txt"), "w", encoding="utf-8") as outfile:
    for i, file in enumerate(files):
        with open(file, "r", encoding="utf-8") as infile:
            outfile.write(infile.read().rstrip())

        # سطر فارغ واحد فقط بين الملفات
        if i != len(files) - 1:
            outfile.write("\n\n")

print(f"تم إنشاء {os.path.join(source_dir, 'All.txt')}")
