import { Routes } from '@angular/router';
import { Login } from './components/login/login';
import { noAuthGuard } from './guards/no-auth-guard';
import { ProfileIndex } from './components/profile-index/profile-index';
import { ProfileEdit } from './components/profile-edit/profile-edit';
import { authGuard } from './guards/auth-guard';
import { Home } from './components/home/home';
import { Friends } from './components/friends/friends';
import { CarCreate } from './components/car-create/car-create';
import { CarShow } from './components/car-show/car-show';
import { CarEdit } from './components/car-edit/car-edit';
import { Register } from './components/register/register';

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
        path: "register",
        component: Register,
        canActivate: [noAuthGuard],
    },
    {
        path: "profile",
        canActivateChild: [authGuard],
        children: [
            {path: "edit", component: ProfileEdit},
            {path: ":id", component: ProfileIndex},
        ]
    },
    {
        path: "friends",
        component: Friends,
        canActivate: [authGuard],
    },
    {
        path: "cars",
        canActivateChild: [authGuard],
        children: [
            {path: "create", component: CarCreate},
            {path: ":id", component: CarShow

            },
            {path: ":id/edit", component: CarEdit},
        ]
    }
];
