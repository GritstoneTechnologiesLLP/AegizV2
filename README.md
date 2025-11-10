# Aegiz Safety Incidents API

Backend service that powers the Aegiz safety incident management UI. The UI mockups show:

- Dashboard with filters (Total, Pending, In Progress, Completed) and incident cards.
- Multi-step incident reporting modal (`Details`, `RCA`, `Submit`).
- Incident detail view with root cause analysis narrative.

This project provides the API needed to support those screens, implemented with FastAPI.

## Current Scope

- Python 3.10+ / FastAPI.
- In-memory persistence to get the first version running quickly (replaceable with a database later).
- Incidents domain only (audits, walks, etc. are out of scope for now).

## Planned Endpoints

| Method | Path | Description |
| --- | --- | --- |
| `GET` | `/incidents` | List incidents with optional status/date filters. |
| `POST` | `/incidents` | Create a new incident with details + RCA. |
| `GET` | `/incidents/{incident_id}` | Retrieve a single incident with RCA answers. |
| `PUT` | `/incidents/{incident_id}` | Update incident information or status. |
| `DELETE` | `/incidents/{incident_id}` | Soft-delete an incident (future). |

## Local Setup

```bash
python -m venv .venv
.venv\Scripts\activate  # on Windows
pip install -r requirements.txt
uvicorn app:app --reload
```

## Next Steps

1. Scaffold the FastAPI application in `app.py`.
2. Define Pydantic models for incidents and RCA answers.
3. Implement the incidents endpoints with in-memory storage.
4. Add filtering logic (status, date range, pagination placeholder).
5. Write basic tests once API solidifies.


