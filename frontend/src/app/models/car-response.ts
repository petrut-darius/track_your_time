import { UserData } from "./user-data";

export interface CarResponse {
    id: number,
    name: string,
    hp: number,
    story: string,
    photos: string,
    user: Pick<UserData, "id" | "username" | "first_name" | "last_name">,
}