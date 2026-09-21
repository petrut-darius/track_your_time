export interface ProfileEditRequest {
    email?: string,
    username?: string,
    last_name?: string,
    first_name?: string,
    avatar?: File | null,
}