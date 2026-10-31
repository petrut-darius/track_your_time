import { CircuitResponse } from "./circuit-response";

export interface StatusResponse {
    name: string;
    circuit: CircuitResponse[];
}