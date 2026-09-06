import { CanActivateFn, Router } from '@angular/router';
import { Auth } from '../services/auth';
import { inject } from '@angular/core';
import { map, take } from 'rxjs';

export const noAuthGuard: CanActivateFn = (route, state) => {
  const auth = inject(Auth);
  const router = inject(Router);

  return auth.authStatus$.pipe(
    take(1),// incearca doar odata
    map(isAuthenticated => isAuthenticated ? router.parseUrl("/") : true)//isAuthenticated = valoarea lui authStatus$
    //nu este folosit router.navigate, pt ca guardurile sunt raspunsuri pt router, si aici eu raspund cu o route
  )
};
