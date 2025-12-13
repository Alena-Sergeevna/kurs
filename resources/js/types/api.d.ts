/**
 * Типы для API ответов
 */

export interface Modul {
    id: number;
    name: string;
    modul_subjects?: ModulSubject[];
    prof_competencies?: ProfCompetency[];
}

export interface ModulSubject {
    id: number;
    name: string;
    id_module: number;
    modul?: Modul;
    prof_competencies?: ProfCompetency[];
    didactic_units?: DidacticUnit[];
    didactic_units_by_pk?: DidacticUnit[];
}

export interface OpSubject {
    id: number;
    name: string;
    prof_competencies?: ProfCompetency[];
    didactic_units?: DidacticUnit[];
    didactic_units_by_pk?: DidacticUnit[];
}

export interface ProfCompetency {
    id: number;
    name: string;
    id_module: number;
    modul?: Modul;
    modul_subjects?: ModulSubject[];
    op_subjects?: OpSubject[];
    op_subjects?: OpSubject[];
}

export interface DidacticUnit {
    id: number;
    type: 'know' | 'be_able' | 'have_practical_experience';
    name: string;
    modul_subjects?: ModulSubject[];
    op_subjects?: OpSubject[];
}

export interface BulkLoadResponse {
    [key: string]: DidacticUnit[];
}

export interface BulkLoadRequest {
    subjects: Array<{
        subject_type: 'modul' | 'op';
        subject_id: number;
        competency_id: number;
    }>;
}

