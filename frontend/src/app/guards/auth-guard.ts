import { CanActivateFn, Router } from '@angular/router';
import { Auth } from '../services/auth';
import { inject } from '@angular/core';
import { map, take } from 'rxjs';

export const authGuard: CanActivateFn = (route, state) => {
  const auth = inject(Auth);
  const router = inject(Router);

  return auth.authStatus$.pipe(
    take(1),
    map(isAuthenticated => isAuthenticated ? true : router.parseUrl("/"))
  )
};
