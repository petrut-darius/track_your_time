import { UserData } from "./user-data";

export interface FriendData {
    id: number;
    status: string;
    friend: UserData;
}