# -*- coding: utf-8 -*-

import os
import re

FOLDERS = ["foldersData/CS", "foldersData/IT", "foldersData/IS", "foldersData/gen"]

def normalize_text(text):
    text = str(text).strip()
    text = text.replace("أ", "ا").replace("إ", "ا").replace("آ", "ا")
    text = text.replace("ى", "ي").replace("ة", "ه")
    text = text.replace("ـ", "")
    text = re.sub(r'[\u064B-\u0652]', '', text)  # التشكيل
    text = re.sub(r'\s+', ' ', text)
    return text.lower().strip()

def parse_committee_line(line):
    # مثال: لجنة 1 مدرج 5
    # أو: لجنة 5 معمل 301
    # أو: لجنة 3 مدرج غير محدد
    m = re.match(r'^لجنة\s+(\d+)\s+(مدرج|معمل)(?:\s+(.+))?$', line.strip())
    if m:
        committee_num = m.group(1)
        place_type = m.group(2)
        place_num = (m.group(3) or "").strip()
        return committee_num, place_type, place_num
    return None, None, None

def parse_exam_file(filepath):
    """
    الملف بالشكل:
    السطر 1: اسم المادة
    السطر 2: لجنة ...
    السطر 3: اليوم + التاريخ
    السطر 4: الفترة ...
    ثم سطر فارغ
    ثم الطلاب: الاسم \t الرقم
    """
    with open(filepath, "r", encoding="utf-8") as f:
        lines = [line.rstrip("\n") for line in f]

    non_empty = [line.strip() for line in lines if line.strip()]
    if len(non_empty) < 4:
        return None

    subject_line = non_empty[0]
    committee_line = non_empty[1]
    day_date_line = non_empty[2]
    period_line = non_empty[3]

    # الطلاب: أي سطر فيه فاصل tab أو على الأقل فيه اسم ثم رقم
    students = []
    for line in non_empty[4:]:
        if "\t" in line:
            name, sid = line.split("\t", 1)
            students.append((name.strip(), sid.strip()))
        else:
            # لو السطر غير مفصول بـ tab نحاول تجاهله
            pass

    committee_num, place_type, place_num = parse_committee_line(committee_line)

    return {
        "filepath": filepath,
        "subject_line": subject_line,
        "committee_line": committee_line,
        "committee_num": committee_num,
        "place_type": place_type,
        "place_num": place_num,
        "day_date_line": day_date_line,
        "period_line": period_line,
        "students": students
    }

def student_matches(query, student_name, student_id):
    q = normalize_text(query)
    name_n = normalize_text(student_name)
    id_n = normalize_text(student_id)

    # تطابق بالاسم الجزئي أو الكامل، أو بالرقم الجزئي أو الكامل
    return (q in name_n) or (q in id_n)

def search_student(query):
    matches = []

    for folder in FOLDERS:
        if not os.path.isdir(folder):
            continue

        for root, _, files in os.walk(folder):
            for file in files:
                if not file.lower().endswith(".txt"):
                    continue

                path = os.path.join(root, file)
                parsed = parse_exam_file(path)
                if not parsed:
                    continue

                for student_name, student_id in parsed["students"]:
                    if student_matches(query, student_name, student_id):
                        matches.append({
                            "student_name": student_name,
                            "student_id": student_id,
                            **parsed
                        })

    return matches

def print_results(query, results):
    if not results:
        print("\nلا توجد نتائج مطابقة.")
        return

    # ترتيب النتائج حسب اسم المادة ثم اللجنة
    def sort_key(x):
        subject = x["subject_line"]
        committee = int(x["committee_num"]) if x["committee_num"] and x["committee_num"].isdigit() else 9999
        return (subject, committee)

    results = sorted(results, key=sort_key)

    print(f"\nتم العثور على {len(results)} نتيجة/نتائج:\n")

    seen = set()
    for r in results:
        # منع تكرار نفس الطالب/نفس الملف لو ظهر أكثر من مرة بشكل غير متوقع
        key = (
            r["student_name"],
            r["student_id"],
            r["subject_line"],
            r["committee_line"],
            r["day_date_line"],
            r["period_line"]
        )
        if key in seen:
            continue
        seen.add(key)

        print("--------------------------------------------------")
        print(f"الطالب : {r['student_name']}")
        print(f"الرقم  : {r['student_id']}")
        print(f"المادة : {r['subject_line']}")
        print(f"اللجنة : {r['committee_line']}")
        print(f"الميعاد: {r['day_date_line']}")
        print(f"الفترة : {r['period_line']}")
        print(f"الملف  : {r['filepath']}")
        print("--------------------------------------------------\n")

if __name__ == "__main__":
    query = input("أدخل اسم الطالب أو الرقم الأكاديمي: ").strip()
    results = search_student(query)
    print_results(query, results)