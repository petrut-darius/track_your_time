import { CircuitResponse } from "./circuit-response";

export interface GradeResponse {
    name: string;
    circuit: CircuitResponse[],
}