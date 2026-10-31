import { UserData } from "./user-data";

export interface LeaderboardCar {
    id: string,
    name: string,
}

export interface LeaderboardResponse{
    user: UserData,
    minutes: number,
    seconds: number
    car: LeaderboardCar,
}