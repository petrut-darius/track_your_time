export interface LoginResponse {
    token: string;
    refresh_token_expiration?: number;
}