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
import { CircuitsIndex } from './components/circuits-index/circuits-index';
import { CarIndex } from './components/car-index/car-index';
import { CircuitsShow } from './components/circuits-show/circuits-show';
import { GradesInfo } from './components/grades-info/grades-info';
import { TypesInfo } from './components/types-info/types-info';
import { StatusesInfo } from './components/statuses-info/statuses-info';
import { ResetPassword } from './components/reset-password/reset-password';
import { ForgotPassword } from './components/forgot-password/forgot-password';

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
        path: "forgot-password",
        component: ForgotPassword,
    },
    {
        path: "reset-password",
        component: ResetPassword,
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
        children: [
            {path: "",component: CarIndex},
            {path: "create", component: CarCreate, canActivate: [authGuard]},
            {path: ":id", component: CarShow, canActivate: [authGuard]},
            {path: ":id/edit", component: CarEdit, canActivate: [authGuard]},
        ]
    },
    {
        path: "circuits",
        children: [
            {path: "", component: CircuitsIndex},
            {path: ":id", component: CircuitsShow},
        ]
    },
    {
        path: "grades",
        component: GradesInfo,
    },
    {
        path: "types",
        component: TypesInfo,
    },
    {
        path: "statuses",
        component: StatusesInfo,
    }
];
