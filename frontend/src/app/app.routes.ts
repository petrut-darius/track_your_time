import { Routes } from '@angular/router';
import { Login } from './components/login/login';
import { noAuthGuard } from './guards/no-auth-guard';
import { ProfileIndex } from './components/profile-index/profile-index';
import { ProfileEdit } from './components/profile-edit/profile-edit';
import { authGuard } from './guards/auth-guard';
import { Home } from './components/home/home';

export const routes: Routes = [
    {
        path: "",
        component: Home,
    },
    {
        path: "login",
        component: Login,
        canActivate: [noAuthGuard]
    },
    {
        path: "profile",
        canActivateChild: [authGuard],
        children: [
            {path: "edit", component: ProfileEdit},
            {path: ":id", component: ProfileIndex},
        ]
    }
];
