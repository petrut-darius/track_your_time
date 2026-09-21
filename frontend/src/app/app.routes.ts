import { Routes } from '@angular/router';
import { Login } from './components/login/login';
import { noAuthGuard } from './guards/no-auth-guard';
import { ProfileIndex } from './components/profile-index/profile-index';
import { ProfileEdit } from './components/profile-edit/profile-edit';
import { authGuard } from './guards/auth-guard';
import { Home } from './components/home/home';
import { Friends } from './components/friends/friends';
import { CarCreate } from './components/car-create/car-create';
import { CarIndex } from './components/car-index/car-index';

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
            {path: ":id", component: CarIndex},
            //{path: ":id/edit"}
        ]
    }
];
