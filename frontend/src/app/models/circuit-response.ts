export interface CircuitResponse {
    id: number,
    name: string,
    image_url: string,
    url: string,
    overview: JSON,
    address: string,
    country: Array<{
        name: string
    }>,
    statuses: Array<{
        name: string
    }>,
    types: Array<{
        name: string
    }>,
    grades: Array<{
        name: string
    }>,
}