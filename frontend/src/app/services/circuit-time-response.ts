import { CarResponse } from "../models/car-response";
import { CircuitResponse } from "../models/circuit-response";

export interface CircuitTimeResponse {
    circuit: CircuitResponse,
    car: CarResponse,
    minutes: number,
    seconds: number,
}