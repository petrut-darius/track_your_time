import { ApplicationConfig, inject, provideAppInitializer, provideBrowserGlobalErrorListeners, provideZonelessChangeDetection } from '@angular/core';
import { provideRouter } from '@angular/router';

import { routes } from './app.routes';
import { provideHttpClient, withInterceptors } from '@angular/common/http';
import { errorInterceptor } from './interceptors/error-interceptor';
import { Auth } from './services/auth';
import { firstValueFrom } from 'rxjs';

export const appConfig: ApplicationConfig = {
  providers: [
    provideBrowserGlobalErrorListeners(),
    provideRouter(routes),
    provideHttpClient(withInterceptors([ errorInterceptor])),
    provideAppInitializer(() => {
      const auth = inject(Auth);
      return firstValueFrom(auth.checkAuthStatus());
    }),
    provideZonelessChangeDetection(),
  ]
};
