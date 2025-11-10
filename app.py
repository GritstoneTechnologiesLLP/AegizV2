from __future__ import annotations

from datetime import date, datetime, time
from enum import Enum
from typing import Dict, Generator, List, Optional
from uuid import uuid4

from fastapi import Depends, FastAPI, HTTPException, Query, status as http_status
from pydantic import BaseModel, Field
from sqlalchemy import (
    Column,
    Date,
    DateTime,
    Enum as SqlEnum,
    ForeignKey,
    Integer,
    String,
    Text,
    Time,
    and_,
    create_engine,
    func,
    or_,
    select,
)
from sqlalchemy.engine import Engine
from sqlalchemy.exc import IntegrityError
from sqlalchemy.orm import Mapped, declarative_base, mapped_column, relationship, sessionmaker
from sqlalchemy.orm.session import Session

from config import get_settings


class IncidentStatus(str, Enum):
    pending = "pending"
    in_progress = "in_progress"
    completed = "completed"


settings = get_settings()
engine: Engine = create_engine(settings.database_url, pool_pre_ping=True, future=True)
SessionLocal = sessionmaker(bind=engine, autoflush=False, autocommit=False, future=True)
Base = declarative_base()


class IncidentORM(Base):
    __tablename__ = "incidents"

    id: Mapped[str] = mapped_column(String(36), primary_key=True)
    incident_title: Mapped[str] = mapped_column(String(255), nullable=False)
    area: Mapped[Optional[str]] = mapped_column(String(255))
    plant: Mapped[Optional[str]] = mapped_column(String(255))
    incident_date: Mapped[date] = mapped_column(Date, nullable=False)
    incident_time: Mapped[Optional[time]] = mapped_column(Time)
    shift: Mapped[Optional[str]] = mapped_column(String(50))
    incident_type: Mapped[Optional[str]] = mapped_column(String(100))
    body_part_affected: Mapped[Optional[str]] = mapped_column(String(100))
    description: Mapped[Optional[str]] = mapped_column(Text)
    comments: Mapped[Optional[str]] = mapped_column(Text)
    status: Mapped[IncidentStatus] = mapped_column(SqlEnum(IncidentStatus), default=IncidentStatus.pending, nullable=False)
    immediate_actions_taken: Mapped[Optional[str]] = mapped_column(Text)
    chairman: Mapped[Optional[str]] = mapped_column(String(255))
    investigator: Mapped[Optional[str]] = mapped_column(String(255))
    safety_officer: Mapped[Optional[str]] = mapped_column(String(255))
    created_at: Mapped[datetime] = mapped_column(DateTime, nullable=False, default=datetime.utcnow)
    updated_at: Mapped[datetime] = mapped_column(DateTime, nullable=False, default=datetime.utcnow, onupdate=datetime.utcnow)

    rca_answers: Mapped[List["RCAAnswerORM"]] = relationship(
        "RCAAnswerORM",
        back_populates="incident",
        cascade="all, delete-orphan",
        order_by="RCAAnswerORM.position",
    )


class RCAAnswerORM(Base):
    __tablename__ = "rca_answers"

    id: Mapped[int] = mapped_column(Integer, primary_key=True, autoincrement=True)
    incident_id: Mapped[str] = mapped_column(String(36), ForeignKey("incidents.id", ondelete="CASCADE"), nullable=False)
    position: Mapped[int] = mapped_column(Integer, default=1, nullable=False)
    question: Mapped[str] = mapped_column(Text, nullable=False)
    answer: Mapped[str] = mapped_column(Text, nullable=False)
    created_at: Mapped[datetime] = mapped_column(DateTime, nullable=False, default=datetime.utcnow)

    incident: Mapped[IncidentORM] = relationship("IncidentORM", back_populates="rca_answers")


def get_db() -> Generator[Session, None, None]:
    db = SessionLocal()
    try:
        yield db
    finally:
        db.close()


class InvestigationTeam(BaseModel):
    chairman: Optional[str] = Field(None, description="Investigation team chairman")
    investigator: Optional[str] = Field(None, description="Investigation team investigator")
    safety_officer: Optional[str] = Field(None, description="Investigation team safety officer")


class RCAAnswer(BaseModel):
    position: int = Field(1, ge=1, description="Display order for the answer")
    question: str = Field(..., description="Root cause analysis prompt/question")
    answer: str = Field(..., description="Answer captured during RCA")


class IncidentBase(BaseModel):
    incident_title: str = Field(..., description="Short title for the incident")
    area: Optional[str] = Field(None, description="Area where the incident occurred")
    plant: Optional[str] = Field(None, description="Plant name")
    incident_date: date = Field(..., description="Date of the incident")
    incident_time: Optional[time] = Field(None, description="Time of the incident")
    shift: Optional[str] = Field(None, description="Shift during which the incident occurred")
    incident_type: Optional[str] = Field(None, description="Incident category")
    body_part_affected: Optional[str] = Field(None, description="Body part affected, if any")
    description: Optional[str] = Field(None, description="Narrative description")
    comments: Optional[str] = Field(None, description="Reviewer comments or summary")


class IncidentCreate(IncidentBase):
    status: IncidentStatus = Field(IncidentStatus.pending, description="Workflow status")
    immediate_actions_taken: Optional[str] = Field(None, description="Immediate actions taken")
    investigation_team: Optional[InvestigationTeam] = Field(None, description="Investigation team")
    rca_answers: List[RCAAnswer] = Field(default_factory=list, description="Root cause analysis answers")


class IncidentUpdate(BaseModel):
    incident_title: Optional[str] = None
    area: Optional[str] = None
    plant: Optional[str] = None
    incident_date: Optional[date] = None
    incident_time: Optional[time] = None
    shift: Optional[str] = None
    incident_type: Optional[str] = None
    body_part_affected: Optional[str] = None
    description: Optional[str] = None
    comments: Optional[str] = None
    status: Optional[IncidentStatus] = None
    immediate_actions_taken: Optional[str] = None
    investigation_team: Optional[InvestigationTeam] = None
    rca_answers: Optional[List[RCAAnswer]] = None


class RCAAnswerRead(RCAAnswer):
    id: int = Field(..., description="RCA answer identifier")
    created_at: datetime


class Incident(BaseModel):
    id: str
    incident_title: str
    area: Optional[str]
    plant: Optional[str]
    incident_date: date
    incident_time: Optional[time]
    shift: Optional[str]
    incident_type: Optional[str]
    body_part_affected: Optional[str]
    description: Optional[str]
    comments: Optional[str]
    status: IncidentStatus
    immediate_actions_taken: Optional[str]
    investigation_team: Optional[InvestigationTeam]
    rca_answers: List[RCAAnswerRead]
    created_at: datetime
    updated_at: datetime


class IncidentListResponse(BaseModel):
    data: List[Incident]
    meta: Dict[str, object]


def to_investigation_team(row: IncidentORM) -> Optional[InvestigationTeam]:
    if not any([row.chairman, row.investigator, row.safety_officer]):
        return None
    return InvestigationTeam(
        chairman=row.chairman,
        investigator=row.investigator,
        safety_officer=row.safety_officer,
    )


def to_incident_model(row: IncidentORM) -> Incident:
    return Incident(
        id=row.id,
        incident_title=row.incident_title,
        area=row.area,
        plant=row.plant,
        incident_date=row.incident_date,
        incident_time=row.incident_time,
        shift=row.shift,
        incident_type=row.incident_type,
        body_part_affected=row.body_part_affected,
        description=row.description,
        comments=row.comments,
        status=row.status,
        immediate_actions_taken=row.immediate_actions_taken,
        investigation_team=to_investigation_team(row),
        rca_answers=[
            RCAAnswerRead(
                id=answer.id,
                position=answer.position,
                question=answer.question,
                answer=answer.answer,
                created_at=answer.created_at,
            )
            for answer in row.rca_answers
        ],
        created_at=row.created_at,
        updated_at=row.updated_at,
    )


app = FastAPI(
    title="Aegiz Safety Incidents API",
    version="0.2.0",
    description="API for managing safety incidents backed by MySQL storage.",
)


@app.get("/health", tags=["System"])
def health_check() -> Dict[str, str]:
    return {"status": "ok", "timestamp": datetime.utcnow().isoformat()}


@app.get("/incidents", response_model=IncidentListResponse, tags=["Incidents"])
def list_incidents(
    status: Optional[IncidentStatus] = Query(None, description="Filter by workflow status"),
    search: Optional[str] = Query(None, description="Case-insensitive search across title, area, and description"),
    start_date: Optional[date] = Query(None, description="Filter incidents on/after this date"),
    end_date: Optional[date] = Query(None, description="Filter incidents on/before this date"),
    page: int = Query(1, ge=1, description="Page number"),
    page_size: int = Query(10, ge=1, le=100, description="Number of incidents per page"),
    db: Session = Depends(get_db),
) -> IncidentListResponse:
    filters = []
    if status:
        filters.append(IncidentORM.status == status)
    if start_date:
        filters.append(IncidentORM.incident_date >= start_date)
    if end_date:
        filters.append(IncidentORM.incident_date <= end_date)

    query = select(IncidentORM).order_by(IncidentORM.incident_date.desc(), IncidentORM.incident_time.desc())
    if filters:
        query = query.where(and_(*filters))
    if search:
        like_term = f"%{search.lower()}%"
        query = query.where(
            or_(
                func.lower(IncidentORM.incident_title).like(like_term),
                func.lower(IncidentORM.description).like(like_term),
                func.lower(IncidentORM.area).like(like_term),
            )
        )

    total = db.scalar(select(func.count()).select_from(query.subquery()))
    page = max(page, 1)
    offset = (page - 1) * page_size
    incidents_rows = (
        db.execute(query.offset(offset).limit(page_size))
        .unique()
        .scalars()
        .all()
    )

    # Preload answers manually to respect ordering
    for incident in incidents_rows:
        incident.rca_answers  # relationship lazy-load

    stat_query = select(IncidentORM.status, func.count()).group_by(IncidentORM.status)
    status_totals = {row[0].value: row[1] for row in db.execute(stat_query)}
    status_totals.setdefault("pending", 0)
    status_totals.setdefault("in_progress", 0)
    status_totals.setdefault("completed", 0)
    status_totals["total"] = sum(status_totals.values())
    status_totals["filtered_total"] = total or 0
    status_totals["page"] = page
    status_totals["page_size"] = page_size
    status_totals["page_count"] = ((total or 0) + page_size - 1) // page_size if total else 0
    status_totals["results_on_page"] = len(incidents_rows)

    return IncidentListResponse(
        data=[to_incident_model(row) for row in incidents_rows],
        meta=status_totals,
    )


@app.post("/incidents", response_model=Incident, status_code=http_status.HTTP_201_CREATED, tags=["Incidents"])
def create_incident(payload: IncidentCreate, db: Session = Depends(get_db)) -> Incident:
    incident_id = str(uuid4())
    db_incident = IncidentORM(
        id=incident_id,
        incident_title=payload.incident_title,
        area=payload.area,
        plant=payload.plant,
        incident_date=payload.incident_date,
        incident_time=payload.incident_time,
        shift=payload.shift,
        incident_type=payload.incident_type,
        body_part_affected=payload.body_part_affected,
        description=payload.description,
        comments=payload.comments,
        status=payload.status,
        immediate_actions_taken=payload.immediate_actions_taken,
        chairman=payload.investigation_team.chairman if payload.investigation_team else None,
        investigator=payload.investigation_team.investigator if payload.investigation_team else None,
        safety_officer=payload.investigation_team.safety_officer if payload.investigation_team else None,
    )
    db.add(db_incident)

    for idx, answer in enumerate(payload.rca_answers, start=1):
        db.add(
            RCAAnswerORM(
                incident_id=incident_id,
                position=answer.position or idx,
                question=answer.question,
                answer=answer.answer,
            )
        )

    try:
        db.commit()
    except IntegrityError as exc:
        db.rollback()
        raise HTTPException(status_code=400, detail="Failed to create incident") from exc

    db.refresh(db_incident)
    return to_incident_model(db_incident)


@app.get("/incidents/{incident_id}", response_model=Incident, tags=["Incidents"])
def get_incident(incident_id: str, db: Session = Depends(get_db)) -> Incident:
    row = db.get(IncidentORM, incident_id)
    if not row:
        raise HTTPException(status_code=404, detail="Incident not found")
    row.rca_answers
    return to_incident_model(row)


@app.put("/incidents/{incident_id}", response_model=Incident, tags=["Incidents"])
def update_incident(incident_id: str, payload: IncidentUpdate, db: Session = Depends(get_db)) -> Incident:
    update_data = payload.model_dump(exclude_unset=True)
    if not update_data:
        raise HTTPException(status_code=400, detail="No fields provided for update")

    row = db.get(IncidentORM, incident_id)
    if not row:
        raise HTTPException(status_code=404, detail="Incident not found")

    for field in (
        "incident_title",
        "area",
        "plant",
        "incident_date",
        "incident_time",
        "shift",
        "incident_type",
        "body_part_affected",
        "description",
        "comments",
        "status",
        "immediate_actions_taken",
    ):
        if field in update_data:
            setattr(row, field, update_data[field])

    if payload.investigation_team is not None:
        row.chairman = payload.investigation_team.chairman
        row.investigator = payload.investigation_team.investigator
        row.safety_officer = payload.investigation_team.safety_officer

    if payload.rca_answers is not None:
        row.rca_answers.clear()
        for idx, answer in enumerate(payload.rca_answers, start=1):
            row.rca_answers.append(
                RCAAnswerORM(
                    incident_id=row.id,
                    position=answer.position or idx,
                    question=answer.question,
                    answer=answer.answer,
                )
            )

    try:
        db.commit()
    except IntegrityError as exc:
        db.rollback()
        raise HTTPException(status_code=400, detail="Failed to update incident") from exc

    db.refresh(row)
    row.rca_answers
    return to_incident_model(row)



