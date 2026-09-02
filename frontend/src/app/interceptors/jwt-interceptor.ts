import { HttpInterceptorFn } from "@angular/common/http";
import { inject } from "@angular/core";
import { Auth } from "../services/auth";

export const jwtInterceptor: HttpInterceptorFn = (req, next) => {
  const authService = inject(Auth);

  if (authService.isLoggedIn()) {
    const token = localStorage.getItem("accessToken");
    req = req.clone({
      setHeaders: {
        Authorization: `Bearer ${token}`,
      },
    });
  }

  return next(req);
};