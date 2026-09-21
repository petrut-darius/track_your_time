export interface ApiError {
    error: string;
    violations: Record<string, string>;
}