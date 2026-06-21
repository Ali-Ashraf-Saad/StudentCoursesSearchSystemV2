import json
import random
import time
from datetime import datetime, timedelta
from pathlib import Path

# =========================
# الإعدادات
# =========================

# مدة التشغيل اللحظي
RUN_DURATION_HOURS = 5 / 60  # 5 minutes

# توليد داتا تاريخية
GENERATE_HISTORY = 0
# تشغيل التوليد اللحظي
RUN_REALTIME = 1

# عدد الأيام التي سيولدها في الداتا التاريخية
HISTORY_DAYS = 1

# الفاصل الزمني بين شخص وشخص

# في التشغيل اللحظي
MIN_WAIT_SECONDS = 2
MAX_WAIT_SECONDS = 7
# في الداتا التاريخية
HISTORY_MIN_STEP_MINUTES = 2
HISTORY_MAX_STEP_MINUTES = 10

# مجلد اللوج
LOG_DIR = Path("counterFiles/logs")
LOG_DIR.mkdir(parents=True, exist_ok=True)

# تفعيل أو تعطيل كل ملف
FILE_SETTINGS = {
    "users.jsonl": {
        "enabled": True,
        "start_range": (10, 20),
    },
    "course.jsonl": {
        "enabled": True,
        "start_range": (10, 20),
    },
    "qa.jsonl": {
        "enabled": True,
        "start_range": (10, 20),
    },
}


# =========================
# أدوات مساعدة
# =========================

def get_enabled_files():
    return [
        file_name
        for file_name, settings in FILE_SETTINGS.items()
        if settings["enabled"]
    ]

def write_record(file_path, count, dt):
    with open(file_path, "a", encoding="utf-8") as f:
        f.write(
            json.dumps(
                {
                    "count": count,
                    "time": dt.strftime("%Y-%m-%d %H:%M:%S"),
                },
                ensure_ascii=False,
                separators=(",", ":"),
            )
            + "\n"
        )

def clear_files(file_names):
    for file_name in file_names:
        (LOG_DIR / file_name).write_text("", encoding="utf-8")

def get_last_count(file_path):
    try:
        if not file_path.exists():
            return 0

        with open(file_path, "rb") as f:
            f.seek(0, 2)  # اذهب إلى نهاية الملف
            end = f.tell()

            if end == 0:
                return 0

            pos = end - 1

            # تجاهل أي \n أو فراغات في آخر الملف
            while pos >= 0:
                f.seek(pos)
                char = f.read(1)

                if char not in (b"\n", b"\r", b" ", b"\t"):
                    break

                pos -= 1

            if pos < 0:
                return 0

            # ارجع إلى بداية آخر سطر
            while pos >= 0:
                f.seek(pos)
                if f.read(1) == b"\n":
                    pos += 1
                    break
                pos -= 1

            if pos < 0:
                pos = 0

            f.seek(pos)
            last_line = f.readline().decode("utf-8").strip()

            if not last_line:
                return 0

            return json.loads(last_line)["count"]

    except Exception as e:
        print(f"Error reading {file_path}: {e}")
        return 0
    
def get_start_count(file_name):
    start_min, start_max = FILE_SETTINGS[file_name]["start_range"]
    return random.randint(start_min, start_max)

def load_current_counts(file_names):
    current_counts = {}

    for file_name in file_names:
        file_path = LOG_DIR / file_name

        last_count = get_last_count(file_path)
        if last_count > 0:
            current_counts[file_name] = last_count
            continue

        current_counts[file_name] = get_start_count(file_name)

    return current_counts

def generate_history(file_names):
    print("Generating historical data...")

    start_time = datetime.now() - timedelta(days=HISTORY_DAYS)
    now = datetime.now()

    current_counts = {}

    for file_name in file_names:
        file_path = LOG_DIR / file_name

        count = get_start_count(file_name)
        current_time = start_time
        last_written = count

        while current_time <= now:
            write_record(file_path, count, current_time)
            last_written = count

            count += 1

            current_time += timedelta(
                minutes=random.randint(HISTORY_MIN_STEP_MINUTES, HISTORY_MAX_STEP_MINUTES),
                seconds=random.randint(0, 59),
            )

        current_counts[file_name] = last_written
        print(f"{file_name}: {last_written:,} last count")

    return current_counts

def random_wait_seconds():
    return random.randint(MIN_WAIT_SECONDS, MAX_WAIT_SECONDS)

def realtime_generation(current_counts, file_names):
    print("\nReal-time generation started...\n")

    end_time = time.time() + (RUN_DURATION_HOURS * 3600)

    while True:
        remaining = end_time - time.time()
        if remaining <= 0:
            break

        wait_time = min(random_wait_seconds(), remaining)
        time.sleep(wait_time)

        if time.time() >= end_time:
            break

        file_name = random.choice(file_names)
        current_counts[file_name] += 1

        now = datetime.now()
        record = {
            "file": file_name,
            "count": current_counts[file_name],
            "time": now.strftime("%Y-%m-%d %H:%M:%S"),
        }

        write_record(LOG_DIR / file_name, current_counts[file_name], now)

        print(
            json.dumps(record, ensure_ascii=False, separators=(",", ":")),
            flush=True,
        )

    print("\nFinished.")

# =========================
# التشغيل
# =========================

def main():
    file_names = get_enabled_files()

    if not file_names:
        print("No enabled files found.")
        return

    if GENERATE_HISTORY:
        clear_files(file_names)
        current_counts = generate_history(file_names)
    else:
        print("History generation is disabled.")
        current_counts = load_current_counts(file_names)

    if RUN_REALTIME:
        realtime_generation(current_counts, file_names)
    else:
        print("Real-time generation is disabled.")

if __name__ == "__main__":
    main()