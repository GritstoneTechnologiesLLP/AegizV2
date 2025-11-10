# Aegiz Backend API Reference

Base URL (local development): `http://127.0.0.1:8000`

Authentication is not required for the current prototype.

---

## Common Conventions
- All endpoints return JSON.
- Timestamps are ISO-8601 strings in UTC.
- Pagination parameters:
  - `page` (default `1`, min `1`)
  - `page_size` (default `10`, min `1`, max `100`)
- Standard response envelope for list endpoints:
  ```json
  {
    "data": [ ... array of items ... ],
    "meta": {
      "total": 5,
      "filtered_total": 5,
      "page": 1,
      "page_size": 10,
      "page_count": 1,
      "results_on_page": 5,
      "pending": 2,
      "in_progress": 1,
      "completed": 2
    }
  }
  ```

---

## System
### GET `/health`
- Returns service liveness.
- Response
  ```json
  { "status": "ok", "timestamp": "2025-11-10T11:58:13.767Z" }
  ```

---

## Incidents
### GET `/incidents`
Query parameters:
| Name | Type | Description |
| --- | --- | --- |
| `status` | `pending \| in_progress \| completed` | Optional workflow filter |
| `search` | `string` | Case-insensitive match against title, description, area |
| `start_date` | `YYYY-MM-DD` | Return incidents on/after this date |
| `end_date` | `YYYY-MM-DD` | Return incidents on/before this date |
| `page`, `page_size` | integers | Pagination |

Example response:
```json
{
  "data": [
    {
      "id": "8fdd7d61-57a9-494b-81bd-1069677916d0",
      "incident_title": "Slip and Fall",
      "area": "Warehouse",
      "plant": "Plant A",
      "incident_date": "2025-11-07",
      "incident_time": "12:00:00",
      "shift": "Day",
      "incident_type": "Injury",
      "body_part_affected": "Leg",
      "description": "Employee slipped on wet floor.",
      "comments": "Investigating",
      "status": "pending",
      "immediate_actions_taken": "Closed area",
      "investigation_team": {
        "chairman": "Sara Adams",
        "investigator": "Sara Andrews",
        "safety_officer": "Alex Doe"
      },
      "rca_answers": [
        {
          "id": 1,
          "position": 1,
          "question": "Why did this incident occur?",
          "answer": "Floor was wet.",
          "created_at": "2025-11-07T12:05:00Z"
        }
      ],
      "created_at": "2025-11-07T12:00:00Z",
      "updated_at": "2025-11-07T12:05:00Z"
    }
  ],
  "meta": {
    "total": 1,
    "filtered_total": 1,
    "page": 1,
    "page_size": 10,
    "page_count": 1,
    "results_on_page": 1,
    "pending": 1,
    "in_progress": 0,
    "completed": 0
  }
}
```

### POST `/incidents`
Request body:
```json
{
  "incident_title": "Slip and Fall",
  "area": "Warehouse",
  "plant": "Plant A",
  "incident_date": "2025-11-07",
  "incident_time": "12:00:00",
  "shift": "Day",
  "incident_type": "Injury",
  "body_part_affected": "Leg",
  "description": "Employee slipped on wet floor.",
  "comments": "Investigating",
  "status": "pending",
  "immediate_actions_taken": "Closed area",
  "investigation_team": {
    "chairman": "Sara Adams",
    "investigator": "Sara Andrews",
    "safety_officer": "Alex Doe"
  },
  "rca_answers": [
    { "position": 1, "question": "Why did this incident occur?", "answer": "Floor was wet." }
  ]
}
```

### GET `/incidents/{incident_id}`
Returns a single incident.

Example response:
```json
{
  "id": "8fdd7d61-57a9-494b-81bd-1069677916d0",
  "incident_title": "Slip and Fall",
  "area": "Warehouse",
  "plant": "Plant A",
  "incident_date": "2025-11-07",
  "incident_time": "12:00:00",
  "shift": "Day",
  "incident_type": "Injury",
  "body_part_affected": "Leg",
  "description": "Employee slipped on wet floor.",
  "comments": "Investigating",
  "status": "pending",
  "immediate_actions_taken": "Closed area",
  "investigation_team": {
    "chairman": "Sara Adams",
    "investigator": "Sara Andrews",
    "safety_officer": "Alex Doe"
  },
  "rca_answers": [
    {
      "id": 1,
      "position": 1,
      "question": "Why did this incident occur?",
      "answer": "Floor was wet.",
      "created_at": "2025-11-07T12:05:00Z"
    }
  ],
  "created_at": "2025-11-07T12:00:00Z",
  "updated_at": "2025-11-07T12:05:00Z"
}
```

### PUT `/incidents/{incident_id}`
- Request body: partial fields from the POST schema. At least one property is required.

Example request:
```json
{
  "status": "in_progress",
  "comments": "Interviewing team",
  "rca_answers": [
    { "position": 1, "question": "Why did this incident occur?", "answer": "Floor was wet." },
    { "position": 2, "question": "Why was the floor wet?", "answer": "Cleaning staff mopped recently." }
  ]
}
```

Example response:
```json
{
  "id": "8fdd7d61-57a9-494b-81bd-1069677916d0",
  "incident_title": "Slip and Fall",
  "area": "Warehouse",
  "plant": "Plant A",
  "incident_date": "2025-11-07",
  "incident_time": "12:00:00",
  "shift": "Day",
  "incident_type": "Injury",
  "body_part_affected": "Leg",
  "description": "Employee slipped on wet floor.",
  "comments": "Interviewing team",
  "status": "in_progress",
  "immediate_actions_taken": "Closed area",
  "investigation_team": {
    "chairman": "Sara Adams",
    "investigator": "Sara Andrews",
    "safety_officer": "Alex Doe"
  },
  "rca_answers": [
    {
      "id": 1,
      "position": 1,
      "question": "Why did this incident occur?",
      "answer": "Floor was wet.",
      "created_at": "2025-11-07T12:05:00Z"
    },
    {
      "id": 2,
      "position": 2,
      "question": "Why was the floor wet?",
      "answer": "Cleaning staff mopped recently.",
      "created_at": "2025-11-07T13:00:00Z"
    }
  ],
  "created_at": "2025-11-07T12:00:00Z",
  "updated_at": "2025-11-07T13:00:00Z"
}
```

---

## Safety Walks
### GET `/safety-walks`
Query parameters:
| Name | Type | Description |
| --- | --- | --- |
| `status` | `pending \| in_progress \| completed` | Optional status filter |
| `search` | `string` | Matches site, area, contact, comments |
| `start_date` / `end_date` | `YYYY-MM-DD` | Date range filters |
| `page`, `page_size` | integers | Pagination |

Example response:
```json
{
  "data": [
    {
      "id": "4fda6f2c-8f89-4d44-8f2c-1bb08600a7b8",
      "walk_date": "2025-11-07",
      "walk_time": "12:00:00",
      "site": "TCO",
      "area": "Fire",
      "mode": "Conversational",
      "contact": "Admin User",
      "is_virtual": false,
      "comments": "General walkthrough",
      "status": "in_progress",
      "reported_by": "Sara Andrews",
      "reported_by_role": "Safety Audit Manager",
      "findings": [
        {
          "id": 1,
          "finding_type": "good_practice",
          "description": "Clear signage.",
          "photos": ["https://example.com/photo.jpg"],
          "signature_url": "data:image/png;base64,...",
          "created_at": "2025-11-07T12:15:00Z"
        }
      ],
      "responses": [
        {
          "id": 1,
          "category": "Work Area",
          "position": 1,
          "question": "Is the area clear?",
          "answer": "Yes",
          "score": 100,
          "created_at": "2025-11-07T12:15:00Z"
        }
      ],
      "created_at": "2025-11-07T12:00:00Z",
      "updated_at": "2025-11-07T12:15:00Z"
    }
  ],
  "meta": {
    "total": 1,
    "filtered_total": 1,
    "page": 1,
    "page_size": 10,
    "page_count": 1,
    "results_on_page": 1,
    "pending": 0,
    "in_progress": 1,
    "completed": 0
  }
}
```

### POST `/safety-walks`
```json
{
  "walk_date": "2025-11-07",
  "walk_time": "12:00:00",
  "site": "TCO",
  "area": "Fire",
  "mode": "Conversational",
  "contact": "Admin User",
  "is_virtual": false,
  "comments": "General walkthrough",
  "status": "in_progress",
  "reported_by": "Sara Andrews",
  "reported_by_role": "Safety Audit Manager",
  "findings": [
    {
      "finding_type": "good_practice",
      "description": "Clear signage.",
      "photos": ["https://example.com/photo.jpg"],
      "signature_url": "data:image/png;base64,..."
    }
  ],
  "responses": [
    {
      "category": "Work Area",
      "position": 1,
      "question": "Is the area clear?",
      "answer": "Yes",
      "score": 100
    }
  ]
}
```

### GET `/safety-walks/{walk_id}`
Example response:
```json
{
  "id": "4fda6f2c-8f89-4d44-8f2c-1bb08600a7b8",
  "walk_date": "2025-11-07",
  "walk_time": "12:00:00",
  "site": "TCO",
  "area": "Fire",
  "mode": "Conversational",
  "contact": "Admin User",
  "is_virtual": false,
  "comments": "General walkthrough",
  "status": "in_progress",
  "reported_by": "Sara Andrews",
  "reported_by_role": "Safety Audit Manager",
  "findings": [
    {
      "id": 1,
      "finding_type": "good_practice",
      "description": "Clear signage.",
      "photos": ["https://example.com/photo.jpg"],
      "signature_url": "data:image/png;base64,...",
      "created_at": "2025-11-07T12:15:00Z"
    }
  ],
  "responses": [
    {
      "id": 1,
      "category": "Work Area",
      "position": 1,
      "question": "Is the area clear?",
      "answer": "Yes",
      "score": 100,
      "created_at": "2025-11-07T12:15:00Z"
    }
  ],
  "created_at": "2025-11-07T12:00:00Z",
  "updated_at": "2025-11-07T12:15:00Z"
}
```

### PUT `/safety-walks/{walk_id}`
Example request:
```json
{
  "status": "completed",
  "comments": "Completed review",
  "findings": [
    {
      "finding_type": "point_of_improvement",
      "description": "Need more signage",
      "photos": [],
      "signature_url": null
    }
  ],
  "responses": [
    {
      "category": "Work Area",
      "position": 1,
      "question": "Is the area clear?",
      "answer": "Yes",
      "score": 90
    }
  ]
}
```
Example response mirrors GET with updated fields.

---

## Audits
### GET `/audits`
Query parameters:
| Name | Type | Description |
| --- | --- | --- |
| `status` | `pending \| in_progress \| completed` | Optional status filter |
| `search` | `string` | Matches title, area, template |
| `start_date` / `end_date` | `YYYY-MM-DD` | Audit date filters |
| `page`, `page_size` | integers | Pagination |

Example response:
```json
{
  "data": [
    {
      "id": "2c77f9dd-3c45-41a5-9cf7-2c71233eaebb",
      "title": "Fire Safety Check",
      "area": "Fire",
      "template": "Fire safety",
      "site": "TCO",
      "contact": "Admin User",
      "is_virtual": false,
      "comments": "Quarterly audit",
      "status": "pending",
      "reported_by": "Sara Andrews",
      "reported_by_role": "Safety Audit Manager",
      "audit_date": "2025-11-11",
      "audit_time": "12:00:00",
      "responses": [
        {
          "id": 1,
          "position": 1,
          "question": "Has the risk assessment been carried out?",
          "answer": "yes",
          "observation": "All documentation current.",
          "created_at": "2025-11-11T12:30:00Z"
        }
      ],
      "observations": [
        {
          "id": 1,
          "note": "Staff well prepared.",
          "created_at": "2025-11-11T12:30:00Z"
        }
      ],
      "created_at": "2025-11-11T12:00:00Z",
      "updated_at": "2025-11-11T12:30:00Z"
    }
  ],
  "meta": {
    "total": 1,
    "filtered_total": 1,
    "page": 1,
    "page_size": 10,
    "page_count": 1,
    "results_on_page": 1,
    "pending": 1,
    "in_progress": 0,
    "completed": 0
  }
}
```

### POST `/audits`
```json
{
  "title": "Fire Safety Check",
  "area": "Fire",
  "template": "Fire safety",
  "site": "TCO",
  "contact": "Admin User",
  "is_virtual": false,
  "comments": "Quarterly audit",
  "status": "pending",
  "reported_by": "Sara Andrews",
  "reported_by_role": "Safety Audit Manager",
  "audit_date": "2025-11-11",
  "audit_time": "12:00:00",
  "responses": [
    {
      "position": 1,
      "question": "Has the risk assessment been carried out?",
      "answer": "yes",
      "observation": "All documentation current."
    }
  ],
  "observations": [
    { "note": "Staff well prepared." }
  ]
}
```

### GET `/audits/{audit_id}`
Example response identical to the sample in GET list for the matching item.

### PUT `/audits/{audit_id}`
Example request:
```json
{
  "status": "completed",
  "comments": "All checks done",
  "responses": [
    {
      "position": 1,
      "question": "Has the risk assessment been carried out?",
      "answer": "yes",
      "observation": "Updated documents on site"
    },
    {
      "position": 2,
      "question": "Are staff trained?",
      "answer": "yes",
      "observation": "Training log available"
    }
  ],
  "observations": [
    { "note": "Consider scheduling refresher training." }
  ]
}
```
Response mirrors GET with updated values.

---

## Users
### GET `/users`
Query parameters:
| Name | Type | Description |
| --- | --- | --- |
| `status` | `active \| inactive` | Optional status filter |
| `search` | `string` | Matches first name, last name, email, phone |
| `page`, `page_size` | integers | Pagination |

Example response:
```json
{
  "data": [
    {
      "id": "a4c66aa3-7899-4f0a-9875-4f76de6b4adb",
      "first_name": "Sara",
      "last_name": "Andrews",
      "email": "sara@example.com",
      "phone": "+1 555 000 0000",
      "profile_image_url": "data:image/png;base64,...",
      "address_line1": "123 25th Avenue",
      "address_line2": "Suite 100",
      "country": "USA",
      "state": "WA",
      "district": "Seattle",
      "zipcode": "98101",
      "status": "active",
      "added_on": "2025-11-10T11:58:13.767689",
      "updated_at": "2025-11-10T11:58:13.767689",
      "roles": [
        {
          "id": 1,
          "role_name": "Safety Audit Manager",
          "created_at": "2025-11-10T11:58:13.767689"
        }
      ],
      "branches": [
        {
          "id": 1,
          "branch_name": "UL Cyber Park",
          "branch_location": "Seattle, USA",
          "created_at": "2025-11-10T11:58:13.767689"
        }
      ]
    }
  ],
  "meta": {
    "total": 1,
    "filtered_total": 1,
    "page": 1,
    "page_size": 10,
    "page_count": 1,
    "results_on_page": 1,
    "active": 1,
    "inactive": 0
  }
}
```

### POST `/users`
```json
{
  "first_name": "Sara",
  "last_name": "Andrews",
  "email": "sara@example.com",
  "phone": "+1 555 000 0000",
  "profile_image_url": "data:image/png;base64,...",
  "address_line1": "123 25th Avenue",
  "address_line2": "Suite 100",
  "country": "USA",
  "state": "WA",
  "district": "Seattle",
  "zipcode": "98101",
  "status": "active",
  "roles": [ { "role_name": "Safety Audit Manager" } ],
  "branches": [ { "branch_name": "UL Cyber Park", "branch_location": "Seattle, USA" } ]
}
```

### GET `/users/{user_id}`
Example response identical to list item for the matching user.

### PUT `/users/{user_id}`
Example request:
```json
{
  "phone": "+1 555 999 0000",
  "status": "inactive",
  "roles": [
    { "role_name": "COO" }
  ],
  "branches": [
    { "branch_name": "Atlanta Branch", "branch_location": "Texas, USA" }
  ]
}
```
Response mirrors GET with updated fields.

---

## Error Handling
- Validation errors return HTTP `422` with details from FastAPI.
- Conflicts (e.g., duplicate email) return HTTP `400` with `{"detail": "..."}`.
- Not found resources return HTTP `404` with `{"detail": "..."}`.
