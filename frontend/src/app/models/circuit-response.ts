import { CountryResponse } from "./country-response"
import { GradeResponse } from "./grade-response"
import { StatusResponse } from "./status-response"
import { TypeResponse } from "./type-response"

export interface Overview {
    content: string
}

export interface CircuitResponse {
    id?: number,
    length?: string,
    name?: string,
    image_url?: string,
    url?: string,
    overview?: Overview[],
    address?: string,
    country?: CountryResponse,
    statuses?: StatusResponse[],
    types?: TypeResponse[],
    grades?: GradeResponse[],
}