import { Routes } from '@angular/router';
import { Login } from './components/login/login';
import { noAuthGuard } from './guards/no-auth-guard';

export const routes: Routes = [
    {
        path: "login",
        component: Login,
        canActivate: [noAuthGuard]
    }
];
