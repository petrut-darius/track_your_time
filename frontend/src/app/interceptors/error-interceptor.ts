import { HttpErrorResponse, HttpInterceptorFn } from "@angular/common/http";
import { inject } from "@angular/core";
import { Auth } from "../services/auth";
import { BehaviorSubject, catchError, filter, switchMap, take, throwError } from "rxjs";
import { Router } from "@angular/router";

let isRefreshing = false;
const refreshTokenSubject = new BehaviorSubject<string | null>(null);

export const errorInterceptor: HttpInterceptorFn = (req, next) => {
    const authService = inject(Auth);
    const router = inject(Router);

  return next(req).pipe(
    catchError((error: unknown) => {
        if(error instanceof HttpErrorResponse && error.status === 401 && !req.url.includes("/api/login_check") && !req.url.includes("/api/token/refresh")) {
            if(!isRefreshing) {
                isRefreshing = true;
                refreshTokenSubject.next(null);

                return authService.refreshToken().pipe(
                    switchMap((response) => {
                        isRefreshing = false;
                        refreshTokenSubject.next(response.token);

                        const retried = req.clone({
                            setHeaders: {Authorization: `Bearer ${response.token}`},
                        });
                        return next(retried);
                    }),
                    catchError((refreshError) => {
                        isRefreshing = false;
                        authService.logout();
                        router.navigate(["/login"]);
                        return throwError(() => refreshError);
                    })
                );
            } else {
                return refreshTokenSubject.pipe(
                    filter((token): token is string => token !== null),
                    take(1),
                    switchMap((token) => {
                        const retried = req.clone({
                            setHeaders: {Authorization: `Bearer ${token}`},
                        });
                        
                        return next(retried);
                    })
                )
            }
            
        }
        return throwError(() => error);
    })
  );
};