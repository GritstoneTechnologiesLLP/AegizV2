from __future__ import annotations

from datetime import date, datetime, time
from enum import Enum
from typing import Dict, Generator, List, Optional
from uuid import uuid4
import json

from fastapi import Depends, FastAPI, HTTPException, Query, status as http_status
from pydantic import BaseModel, Field
from sqlalchemy import (
    Column,
    Date,
    DateTime,
    Enum as SqlEnum,
    ForeignKey,
    Integer,
    Numeric,
    String,
    Text,
    Time,
    and_,
    create_engine,
    func,
    or_,
    select,
)
from sqlalchemy.dialects.mysql import LONGTEXT
from sqlalchemy.engine import Engine
from sqlalchemy.exc import IntegrityError
from sqlalchemy.orm import Mapped, declarative_base, mapped_column, relationship, sessionmaker
from sqlalchemy.orm.session import Session

from config import get_settings


class IncidentStatus(str, Enum):
    pending = "pending"
    in_progress = "in_progress"
    completed = "completed"


class FindingType(str, Enum):
    good_practice = "good_practice"
    point_of_improvement = "point_of_improvement"


class AuditAnswer(str, Enum):
    yes = "yes"
    no = "no"
    na = "na"


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


class SafetyWalkORM(Base):
    __tablename__ = "safety_walks"

    id: Mapped[str] = mapped_column(String(36), primary_key=True)
    walk_date: Mapped[date] = mapped_column(Date, nullable=False)
    walk_time: Mapped[Optional[time]] = mapped_column(Time)
    site: Mapped[str] = mapped_column(String(255), nullable=False)
    area: Mapped[Optional[str]] = mapped_column(String(255))
    mode: Mapped[Optional[str]] = mapped_column(String(100))
    contact: Mapped[Optional[str]] = mapped_column(String(255))
    is_virtual: Mapped[int] = mapped_column(Integer, default=0, nullable=False)
    comments: Mapped[Optional[str]] = mapped_column(Text)
    status: Mapped[IncidentStatus] = mapped_column(SqlEnum(IncidentStatus), default=IncidentStatus.pending, nullable=False)
    reported_by: Mapped[Optional[str]] = mapped_column(String(255))
    reported_by_role: Mapped[Optional[str]] = mapped_column(String(255))
    created_at: Mapped[datetime] = mapped_column(DateTime, nullable=False, default=datetime.utcnow)
    updated_at: Mapped[datetime] = mapped_column(DateTime, nullable=False, default=datetime.utcnow, onupdate=datetime.utcnow)

    findings: Mapped[List["SafetyWalkFindingORM"]] = relationship(
        "SafetyWalkFindingORM",
        back_populates="safety_walk",
        cascade="all, delete-orphan",
        order_by="SafetyWalkFindingORM.id",
    )
    responses: Mapped[List["SafetyWalkResponseORM"]] = relationship(
        "SafetyWalkResponseORM",
        back_populates="safety_walk",
        cascade="all, delete-orphan",
        order_by="SafetyWalkResponseORM.position",
    )


class SafetyWalkFindingORM(Base):
    __tablename__ = "safety_walk_findings"

    id: Mapped[int] = mapped_column(Integer, primary_key=True, autoincrement=True)
    safety_walk_id: Mapped[str] = mapped_column(String(36), ForeignKey("safety_walks.id", ondelete="CASCADE"), nullable=False)
    finding_type: Mapped[FindingType] = mapped_column(SqlEnum(FindingType), nullable=False)
    description: Mapped[Optional[str]] = mapped_column(Text)
    signature_url: Mapped[Optional[str]] = mapped_column(LONGTEXT)
    photos_json: Mapped[Optional[str]] = mapped_column(Text)
    created_at: Mapped[datetime] = mapped_column(DateTime, nullable=False, default=datetime.utcnow)

    safety_walk: Mapped[SafetyWalkORM] = relationship("SafetyWalkORM", back_populates="findings")


class SafetyWalkResponseORM(Base):
    __tablename__ = "safety_walk_responses"

    id: Mapped[int] = mapped_column(Integer, primary_key=True, autoincrement=True)
    safety_walk_id: Mapped[str] = mapped_column(String(36), ForeignKey("safety_walks.id", ondelete="CASCADE"), nullable=False)
    category: Mapped[Optional[str]] = mapped_column(String(255))
    position: Mapped[int] = mapped_column(Integer, default=1, nullable=False)
    question: Mapped[str] = mapped_column(Text, nullable=False)
    answer: Mapped[Optional[str]] = mapped_column(Text)
    score: Mapped[Optional[float]] = mapped_column(Numeric(5, 2))
    created_at: Mapped[datetime] = mapped_column(DateTime, nullable=False, default=datetime.utcnow)

    safety_walk: Mapped[SafetyWalkORM] = relationship("SafetyWalkORM", back_populates="responses")


class AuditORM(Base):
    __tablename__ = "audits"

    id: Mapped[str] = mapped_column(String(36), primary_key=True)
    title: Mapped[str] = mapped_column(String(255), nullable=False)
    area: Mapped[Optional[str]] = mapped_column(String(255))
    template: Mapped[Optional[str]] = mapped_column(String(255))
    site: Mapped[Optional[str]] = mapped_column(String(255))
    contact: Mapped[Optional[str]] = mapped_column(String(255))
    is_virtual: Mapped[int] = mapped_column(Integer, default=0, nullable=False)
    comments: Mapped[Optional[str]] = mapped_column(Text)
    status: Mapped[IncidentStatus] = mapped_column(SqlEnum(IncidentStatus), default=IncidentStatus.pending, nullable=False)
    reported_by: Mapped[Optional[str]] = mapped_column(String(255))
    reported_by_role: Mapped[Optional[str]] = mapped_column(String(255))
    audit_date: Mapped[date] = mapped_column(Date, nullable=False)
    audit_time: Mapped[Optional[time]] = mapped_column(Time)
    created_at: Mapped[datetime] = mapped_column(DateTime, nullable=False, default=datetime.utcnow)
    updated_at: Mapped[datetime] = mapped_column(DateTime, nullable=False, default=datetime.utcnow, onupdate=datetime.utcnow)

    responses: Mapped[List["AuditResponseORM"]] = relationship(
        "AuditResponseORM",
        back_populates="audit",
        cascade="all, delete-orphan",
        order_by="AuditResponseORM.position",
    )
    observations: Mapped[List["AuditObservationORM"]] = relationship(
        "AuditObservationORM",
        back_populates="audit",
        cascade="all, delete-orphan",
        order_by="AuditObservationORM.id",
    )


class AuditResponseORM(Base):
    __tablename__ = "audit_responses"

    id: Mapped[int] = mapped_column(Integer, primary_key=True, autoincrement=True)
    audit_id: Mapped[str] = mapped_column(String(36), ForeignKey("audits.id", ondelete="CASCADE"), nullable=False)
    position: Mapped[int] = mapped_column(Integer, default=1, nullable=False)
    question: Mapped[str] = mapped_column(Text, nullable=False)
    answer: Mapped[AuditAnswer] = mapped_column(SqlEnum(AuditAnswer), default=AuditAnswer.na, nullable=False)
    observation: Mapped[Optional[str]] = mapped_column(Text)
    created_at: Mapped[datetime] = mapped_column(DateTime, nullable=False, default=datetime.utcnow)

    audit: Mapped[AuditORM] = relationship("AuditORM", back_populates="responses")


class AuditObservationORM(Base):
    __tablename__ = "audit_observations"

    id: Mapped[int] = mapped_column(Integer, primary_key=True, autoincrement=True)
    audit_id: Mapped[str] = mapped_column(String(36), ForeignKey("audits.id", ondelete="CASCADE"), nullable=False)
    note: Mapped[str] = mapped_column(Text, nullable=False)
    created_at: Mapped[datetime] = mapped_column(DateTime, nullable=False, default=datetime.utcnow)

    audit: Mapped[AuditORM] = relationship("AuditORM", back_populates="observations")


Base.metadata.create_all(bind=engine)


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


class SafetyWalkFindingBase(BaseModel):
    finding_type: FindingType = Field(..., description="Type of finding")
    description: Optional[str] = Field(None, description="Finding description")
    photos: List[str] = Field(default_factory=list, description="Photo URLs associated with the finding")
    signature_url: Optional[str] = Field(None, description="Signature asset URL")


class SafetyWalkFindingCreate(SafetyWalkFindingBase):
    pass


class SafetyWalkFinding(SafetyWalkFindingBase):
    id: int
    created_at: datetime


class SafetyWalkResponseBase(BaseModel):
    category: Optional[str] = Field(None, description="Checklist category")
    position: int = Field(1, ge=1, description="Display order")
    question: str = Field(..., description="Question text")
    answer: Optional[str] = Field(None, description="Answer provided")
    score: Optional[float] = Field(None, ge=0, le=100, description="Score or completion percentage")


class SafetyWalkResponseCreate(SafetyWalkResponseBase):
    pass


class SafetyWalkResponse(SafetyWalkResponseBase):
    id: int
    created_at: datetime


class SafetyWalkBase(BaseModel):
    walk_date: date = Field(..., description="Date of the safety walk")
    walk_time: Optional[time] = Field(None, description="Time of the safety walk")
    site: str = Field(..., description="Site name")
    area: Optional[str] = Field(None, description="Area visited")
    mode: Optional[str] = Field(None, description="Audit mode")
    contact: Optional[str] = Field(None, description="Primary contact")
    is_virtual: bool = Field(False, description="Indicates if the walk was virtual")
    comments: Optional[str] = Field(None, description="Additional comments")
    status: IncidentStatus = Field(IncidentStatus.pending, description="Workflow status")
    reported_by: Optional[str] = Field(None, description="Reporter name")
    reported_by_role: Optional[str] = Field(None, description="Reporter role")


class SafetyWalkCreate(SafetyWalkBase):
    findings: List[SafetyWalkFindingCreate] = Field(default_factory=list)
    responses: List[SafetyWalkResponseCreate] = Field(default_factory=list)


class SafetyWalkUpdate(BaseModel):
    walk_date: Optional[date] = None
    walk_time: Optional[time] = None
    site: Optional[str] = None
    area: Optional[str] = None
    mode: Optional[str] = None
    contact: Optional[str] = None
    is_virtual: Optional[bool] = None
    comments: Optional[str] = None
    status: Optional[IncidentStatus] = None
    reported_by: Optional[str] = None
    reported_by_role: Optional[str] = None
    findings: Optional[List[SafetyWalkFindingCreate]] = None
    responses: Optional[List[SafetyWalkResponseCreate]] = None


class SafetyWalk(SafetyWalkBase):
    id: str
    findings: List[SafetyWalkFinding]
    responses: List[SafetyWalkResponse]
    created_at: datetime
    updated_at: datetime


class SafetyWalkListResponse(BaseModel):
    data: List[SafetyWalk]
    meta: Dict[str, object]


class AuditResponseBase(BaseModel):
    position: int = Field(1, ge=1, description="Display order")
    question: str = Field(..., description="Audit question")
    answer: AuditAnswer = Field(AuditAnswer.na, description="Answer selection")
    observation: Optional[str] = Field(None, description="Additional observation per question")


class AuditResponseCreate(AuditResponseBase):
    pass


class AuditResponse(AuditResponseBase):
    id: int
    created_at: datetime


class AuditObservationBase(BaseModel):
    note: str = Field(..., description="Observation note")


class AuditObservationCreate(AuditObservationBase):
    pass


class AuditObservation(AuditObservationBase):
    id: int
    created_at: datetime


class AuditBase(BaseModel):
    title: str = Field(..., description="Audit title")
    area: Optional[str] = Field(None, description="Area of audit")
    template: Optional[str] = Field(None, description="Template name")
    site: Optional[str] = Field(None, description="Site name")
    contact: Optional[str] = Field(None, description="Contact person")
    is_virtual: bool = Field(False, description="Whether audit is virtual")
    comments: Optional[str] = Field(None, description="General comments")
    status: IncidentStatus = Field(IncidentStatus.pending, description="Workflow status")
    reported_by: Optional[str] = Field(None, description="Reporter name")
    reported_by_role: Optional[str] = Field(None, description="Reporter role")
    audit_date: date = Field(..., description="Audit date")
    audit_time: Optional[time] = Field(None, description="Audit time")


class AuditCreate(AuditBase):
    responses: List[AuditResponseCreate] = Field(default_factory=list)
    observations: List[AuditObservationCreate] = Field(default_factory=list)


class AuditUpdate(BaseModel):
    title: Optional[str] = None
    area: Optional[str] = None
    template: Optional[str] = None
    site: Optional[str] = None
    contact: Optional[str] = None
    is_virtual: Optional[bool] = None
    comments: Optional[str] = None
    status: Optional[IncidentStatus] = None
    reported_by: Optional[str] = None
    reported_by_role: Optional[str] = None
    audit_date: Optional[date] = None
    audit_time: Optional[time] = None
    responses: Optional[List[AuditResponseCreate]] = None
    observations: Optional[List[AuditObservationCreate]] = None


class Audit(AuditBase):
    id: str
    responses: List[AuditResponse]
    observations: List[AuditObservation]
    created_at: datetime
    updated_at: datetime


class AuditListResponse(BaseModel):
    data: List[Audit]
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


def to_safety_walk_model(row: SafetyWalkORM) -> SafetyWalk:
    return SafetyWalk(
        id=row.id,
        walk_date=row.walk_date,
        walk_time=row.walk_time,
        site=row.site,
        area=row.area,
        mode=row.mode,
        contact=row.contact,
        is_virtual=bool(row.is_virtual),
        comments=row.comments,
        status=row.status,
        reported_by=row.reported_by,
        reported_by_role=row.reported_by_role,
        findings=[
            SafetyWalkFinding(
                id=finding.id,
                finding_type=finding.finding_type,
                description=finding.description,
                photos=json.loads(finding.photos_json) if finding.photos_json else [],
                signature_url=finding.signature_url,
                created_at=finding.created_at,
            )
            for finding in row.findings
        ],
        responses=[
            SafetyWalkResponse(
                id=response.id,
                category=response.category,
                position=response.position,
                question=response.question,
                answer=response.answer,
                score=float(response.score) if response.score is not None else None,
                created_at=response.created_at,
            )
            for response in row.responses
        ],
        created_at=row.created_at,
        updated_at=row.updated_at,
    )


def to_audit_model(row: AuditORM) -> Audit:
    return Audit(
        id=row.id,
        title=row.title,
        area=row.area,
        template=row.template,
        site=row.site,
        contact=row.contact,
        is_virtual=bool(row.is_virtual),
        comments=row.comments,
        status=row.status,
        reported_by=row.reported_by,
        reported_by_role=row.reported_by_role,
        audit_date=row.audit_date,
        audit_time=row.audit_time,
        responses=[
            AuditResponse(
                id=response.id,
                position=response.position,
                question=response.question,
                answer=response.answer,
                observation=response.observation,
                created_at=response.created_at,
            )
            for response in row.responses
        ],
        observations=[
            AuditObservation(
                id=observation.id,
                note=observation.note,
                created_at=observation.created_at,
            )
            for observation in row.observations
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


@app.get("/safety-walks", response_model=SafetyWalkListResponse, tags=["Safety Walks"])
def list_safety_walks(
    status: Optional[IncidentStatus] = Query(None, description="Filter by workflow status"),
    search: Optional[str] = Query(None, description="Search across site, area, contact"),
    start_date: Optional[date] = Query(None, description="Filter safety walks on/after this date"),
    end_date: Optional[date] = Query(None, description="Filter safety walks on/before this date"),
    page: int = Query(1, ge=1, description="Page number"),
    page_size: int = Query(10, ge=1, le=100, description="Items per page"),
    db: Session = Depends(get_db),
) -> SafetyWalkListResponse:
    filters = []
    if status:
        filters.append(SafetyWalkORM.status == status)
    if start_date:
        filters.append(SafetyWalkORM.walk_date >= start_date)
    if end_date:
        filters.append(SafetyWalkORM.walk_date <= end_date)

    query = select(SafetyWalkORM).order_by(SafetyWalkORM.walk_date.desc(), SafetyWalkORM.walk_time.desc())
    if filters:
        query = query.where(and_(*filters))
    if search:
        like_term = f"%{search.lower()}%"
        query = query.where(
            or_(
                func.lower(SafetyWalkORM.site).like(like_term),
                func.lower(SafetyWalkORM.area).like(like_term),
                func.lower(SafetyWalkORM.contact).like(like_term),
                func.lower(SafetyWalkORM.comments).like(like_term),
            )
        )

    total = db.scalar(select(func.count()).select_from(query.subquery()))
    page = max(page, 1)
    offset = (page - 1) * page_size
    rows = db.execute(query.offset(offset).limit(page_size)).unique().scalars().all()
    for row in rows:
        row.findings
        row.responses

    stat_query = select(SafetyWalkORM.status, func.count()).group_by(SafetyWalkORM.status)
    status_totals = {row[0].value: row[1] for row in db.execute(stat_query)}
    for value in (IncidentStatus.pending.value, IncidentStatus.in_progress.value, IncidentStatus.completed.value):
        status_totals.setdefault(value, 0)
    status_totals["total"] = sum(status_totals.values())
    status_totals["filtered_total"] = total or 0
    status_totals["page"] = page
    status_totals["page_size"] = page_size
    status_totals["page_count"] = ((total or 0) + page_size - 1) // page_size if total else 0
    status_totals["results_on_page"] = len(rows)

    return SafetyWalkListResponse(
        data=[to_safety_walk_model(row) for row in rows],
        meta=status_totals,
    )


@app.post("/safety-walks", response_model=SafetyWalk, status_code=http_status.HTTP_201_CREATED, tags=["Safety Walks"])
def create_safety_walk(payload: SafetyWalkCreate, db: Session = Depends(get_db)) -> SafetyWalk:
    walk_id = str(uuid4())
    db_walk = SafetyWalkORM(
        id=walk_id,
        walk_date=payload.walk_date,
        walk_time=payload.walk_time,
        site=payload.site,
        area=payload.area,
        mode=payload.mode,
        contact=payload.contact,
        is_virtual=1 if payload.is_virtual else 0,
        comments=payload.comments,
        status=payload.status,
        reported_by=payload.reported_by,
        reported_by_role=payload.reported_by_role,
    )
    db.add(db_walk)

    for finding in payload.findings:
        db_walk.findings.append(
            SafetyWalkFindingORM(
                finding_type=finding.finding_type,
                description=finding.description,
                signature_url=finding.signature_url,
                photos_json=json.dumps(finding.photos) if finding.photos else None,
            )
        )

    for response in payload.responses:
        db_walk.responses.append(
            SafetyWalkResponseORM(
                category=response.category,
                position=response.position,
                question=response.question,
                answer=response.answer,
                score=response.score,
            )
        )

    try:
        db.commit()
    except IntegrityError as exc:
        db.rollback()
        raise HTTPException(status_code=400, detail="Failed to create safety walk") from exc

    db.refresh(db_walk)
    db_walk.findings
    db_walk.responses
    return to_safety_walk_model(db_walk)


@app.get("/safety-walks/{walk_id}", response_model=SafetyWalk, tags=["Safety Walks"])
def get_safety_walk(walk_id: str, db: Session = Depends(get_db)) -> SafetyWalk:
    row = db.get(SafetyWalkORM, walk_id)
    if not row:
        raise HTTPException(status_code=404, detail="Safety walk not found")
    row.findings
    row.responses
    return to_safety_walk_model(row)


@app.put("/safety-walks/{walk_id}", response_model=SafetyWalk, tags=["Safety Walks"])
def update_safety_walk(walk_id: str, payload: SafetyWalkUpdate, db: Session = Depends(get_db)) -> SafetyWalk:
    update_data = payload.model_dump(exclude_unset=True)
    if not update_data:
        raise HTTPException(status_code=400, detail="No fields provided for update")

    row = db.get(SafetyWalkORM, walk_id)
    if not row:
        raise HTTPException(status_code=404, detail="Safety walk not found")

    for field in (
        "walk_date",
        "walk_time",
        "site",
        "area",
        "mode",
        "contact",
        "comments",
        "status",
        "reported_by",
        "reported_by_role",
    ):
        if field in update_data:
            setattr(row, field, update_data[field])

    if "is_virtual" in update_data:
        row.is_virtual = 1 if update_data["is_virtual"] else 0

    if payload.findings is not None:
        row.findings.clear()
        for finding in payload.findings:
            row.findings.append(
                SafetyWalkFindingORM(
                    finding_type=finding.finding_type,
                    description=finding.description,
                    signature_url=finding.signature_url,
                    photos_json=json.dumps(finding.photos) if finding.photos else None,
                )
            )

    if payload.responses is not None:
        row.responses.clear()
        for response in payload.responses:
            row.responses.append(
                SafetyWalkResponseORM(
                    category=response.category,
                    position=response.position,
                    question=response.question,
                    answer=response.answer,
                    score=response.score,
                )
            )

    try:
        db.commit()
    except IntegrityError as exc:
        db.rollback()
        raise HTTPException(status_code=400, detail="Failed to update safety walk") from exc

    db.refresh(row)
    row.findings
    row.responses
    return to_safety_walk_model(row)


@app.get("/audits", response_model=AuditListResponse, tags=["Audits"])
def list_audits(
    status: Optional[IncidentStatus] = Query(None, description="Filter by workflow status"),
    search: Optional[str] = Query(None, description="Search across title, area, template"),
    start_date: Optional[date] = Query(None, description="Audits on/after this date"),
    end_date: Optional[date] = Query(None, description="Audits on/before this date"),
    page: int = Query(1, ge=1, description="Page number"),
    page_size: int = Query(10, ge=1, le=100, description="Items per page"),
    db: Session = Depends(get_db),
) -> AuditListResponse:
    filters = []
    if status:
        filters.append(AuditORM.status == status)
    if start_date:
        filters.append(AuditORM.audit_date >= start_date)
    if end_date:
        filters.append(AuditORM.audit_date <= end_date)

    query = select(AuditORM).order_by(AuditORM.audit_date.desc(), AuditORM.audit_time.desc())
    if filters:
        query = query.where(and_(*filters))
    if search:
        like_term = f"%{search.lower()}%"
        query = query.where(
            or_(
                func.lower(AuditORM.title).like(like_term),
                func.lower(AuditORM.area).like(like_term),
                func.lower(AuditORM.template).like(like_term),
            )
        )

    total = db.scalar(select(func.count()).select_from(query.subquery()))
    page = max(page, 1)
    offset = (page - 1) * page_size
    rows = db.execute(query.offset(offset).limit(page_size)).unique().scalars().all()
    for row in rows:
        row.responses
        row.observations

    stat_query = select(AuditORM.status, func.count()).group_by(AuditORM.status)
    status_totals = {row[0].value: row[1] for row in db.execute(stat_query)}
    for value in (IncidentStatus.pending.value, IncidentStatus.in_progress.value, IncidentStatus.completed.value):
        status_totals.setdefault(value, 0)
    status_totals["total"] = sum(status_totals.values())
    status_totals["filtered_total"] = total or 0
    status_totals["page"] = page
    status_totals["page_size"] = page_size
    status_totals["page_count"] = ((total or 0) + page_size - 1) // page_size if total else 0
    status_totals["results_on_page"] = len(rows)

    return AuditListResponse(data=[to_audit_model(row) for row in rows], meta=status_totals)


@app.post("/audits", response_model=Audit, status_code=http_status.HTTP_201_CREATED, tags=["Audits"])
def create_audit(payload: AuditCreate, db: Session = Depends(get_db)) -> Audit:
    audit_id = str(uuid4())
    db_audit = AuditORM(
        id=audit_id,
        title=payload.title,
        area=payload.area,
        template=payload.template,
        site=payload.site,
        contact=payload.contact,
        is_virtual=1 if payload.is_virtual else 0,
        comments=payload.comments,
        status=payload.status,
        reported_by=payload.reported_by,
        reported_by_role=payload.reported_by_role,
        audit_date=payload.audit_date,
        audit_time=payload.audit_time,
    )
    db.add(db_audit)

    for response in payload.responses:
        db_audit.responses.append(
            AuditResponseORM(
                position=response.position,
                question=response.question,
                answer=response.answer,
                observation=response.observation,
            )
        )

    for observation in payload.observations:
        db_audit.observations.append(
            AuditObservationORM(
                note=observation.note,
            )
        )

    try:
        db.commit()
    except IntegrityError as exc:
        db.rollback()
        raise HTTPException(status_code=400, detail="Failed to create audit") from exc

    db.refresh(db_audit)
    db_audit.responses
    db_audit.observations
    return to_audit_model(db_audit)


@app.get("/audits/{audit_id}", response_model=Audit, tags=["Audits"])
def get_audit(audit_id: str, db: Session = Depends(get_db)) -> Audit:
    row = db.get(AuditORM, audit_id)
    if not row:
        raise HTTPException(status_code=404, detail="Audit not found")
    row.responses
    row.observations
    return to_audit_model(row)


@app.put("/audits/{audit_id}", response_model=Audit, tags=["Audits"])
def update_audit(audit_id: str, payload: AuditUpdate, db: Session = Depends(get_db)) -> Audit:
    update_data = payload.model_dump(exclude_unset=True)
    if not update_data:
        raise HTTPException(status_code=400, detail="No fields provided for update")

    row = db.get(AuditORM, audit_id)
    if not row:
        raise HTTPException(status_code=404, detail="Audit not found")

    for field in (
        "title",
        "area",
        "template",
        "site",
        "contact",
        "comments",
        "status",
        "reported_by",
        "reported_by_role",
        "audit_date",
        "audit_time",
    ):
        if field in update_data:
            setattr(row, field, update_data[field])

    if "is_virtual" in update_data:
        row.is_virtual = 1 if update_data["is_virtual"] else 0

    if payload.responses is not None:
        row.responses.clear()
        for response in payload.responses:
            row.responses.append(
                AuditResponseORM(
                    position=response.position,
                    question=response.question,
                    answer=response.answer,
                    observation=response.observation,
                )
            )

    if payload.observations is not None:
        row.observations.clear()
        for observation in payload.observations:
            row.observations.append(
                AuditObservationORM(
                    note=observation.note,
                )
            )

    try:
        db.commit()
    except IntegrityError as exc:
        db.rollback()
        raise HTTPException(status_code=400, detail="Failed to update audit") from exc

    db.refresh(row)
    row.responses
    row.observations
    return to_audit_model(row)

