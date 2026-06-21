import json
import random
from datetime import datetime, timedelta
from pathlib import Path

END_DATE = datetime(2026, 6, 30, 1, 0, 0)

FILES = {
    "users.jsonl": 25000,
    "course.jsonl": 18000,
    "qa.jsonl": 12000,
}

Path("logs").mkdir(exist_ok=True)


def generate_counter_file(output_file, total_records):
    current_time = END_DATE
    current_count = total_records

    records = []

    while current_count > 0:
        records.append({
            "count": current_count,
            "time": current_time.strftime("%Y-%m-%d %H:%M:%S")
        })

        current_count -= 1

        hour = current_time.hour
        weekday = current_time.weekday()

        weekend = weekday in (4, 5)

        if 2 <= hour < 7:
            minutes = random.randint(20, 180)

        elif 7 <= hour < 10:
            minutes = random.randint(3, 25)

        elif 10 <= hour < 17:
            minutes = random.randint(1, 12)

        elif 17 <= hour < 23:
            minutes = random.randint(1, 5)

        else:
            minutes = random.randint(2, 15)

        if weekend:
            minutes *= random.uniform(0.6, 0.9)

        current_time -= timedelta(
            minutes=minutes,
            seconds=random.randint(0, 59)
        )

    records.reverse()

    with open(output_file, "w", encoding="utf-8") as f:
        for record in records:
            f.write(
                json.dumps(
                    record,
                    ensure_ascii=False,
                    separators=(",", ":")
                )
                + "\n"
            )

    print(
        f"{output_file}: "
        f"{len(records):,} records | "
        f"{records[0]['time']} -> {records[-1]['time']}"
    )


for filename, count in FILES.items():
    generate_counter_file(
        Path("counterFiles/logs") / filename,
        count
    )

print("\nDone.")