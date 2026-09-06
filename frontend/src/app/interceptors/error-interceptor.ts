import { HttpErrorResponse, HttpInterceptorFn } from "@angular/common/http";
import { inject } from "@angular/core";
import { Auth } from "../services/auth";
import { BehaviorSubject, catchError, filter, switchMap, take, throwError } from "rxjs";
import { Router } from "@angular/router";

let isRefreshing = false;
const refreshComplete$ = new BehaviorSubject<boolean>(false);//un observable

export const errorInterceptor: HttpInterceptorFn = (req, next) => {
    const authService = inject(Auth);
    const router = inject(Router);

  return next(req).pipe(//ce rezulta next(req) trece prin pipe
    catchError((error: unknown) => {
        if(error instanceof HttpErrorResponse && error.status === 401 && !req.url.includes("/api/login_check") && !req.url.includes("/api/token/refresh")) {
            if(!isRefreshing) {
                isRefreshing = true;
                refreshComplete$.next(false);

                return authService.refreshToken().pipe(
                    switchMap(() => {//switchMap - o functie care de fiecare cand vede ca ii este schimbat parametrul o ia de la capat
                        isRefreshing = false;
                        refreshComplete$.next(true);
                        return next(req);
                    }),
                    catchError((refreshError) => {
                        isRefreshing = false;
                        authService.logout();
                        router.navigate(["/login"]);
                        return throwError(() => refreshError);
                    })
                );
            } else {
                return refreshComplete$.pipe(
                    filter((done) => done === true),//done valoarea lui refreshComplete$
                    take(1),
                    switchMap((success) => success ? next(req) : throwError(() => new Error("Session expired.")))
                )
            }            
        }
        return throwError(() => error);
    })
  );
};

/*

  ### Purpose                                                                                                                                                                                                      
                                                                                                                                                                                                                   
  An HTTP Interceptor that sits in the HTTP pipeline. Whenever an HTTP request returns a 401 Unauthorized (due to an expired access token cookie):                                                                 
                                                                                                                                                                                                                   
  1. Pauses the failed request.                                                                                                                                                                                    
  2. Calls the backend refresh endpoint to get a new token.                                                                                                                                                        
  3. Queues any concurrent requests that also fail with 401 while the refresh is ongoing.                                                                                                                          
  4. Once the refresh completes, retries the original request(s).                                                                                                                                                  
  5. If the refresh fails, logs the user out and sends them to /login.                                                                                                                                             
  ──────                                                                                                                                                                                                           
  ### Line-by-Line Breakdown                                                                                                                                                                                       
                                                                                                                                                                                                                   
  #### Imports & Module-level State                                                                                                                                                                                
                                                                                                                                                                                                                   
    1: import { HttpErrorResponse, HttpInterceptorFn } from "@angular/common/http";                                                                                                                                
    2: import { inject } from "@angular/core";                                                                                                                                                                     
    3: import { Auth } from "../services/auth";                                                                                                                                                                    
    4: import { BehaviorSubject, catchError, filter, switchMap, take, throwError } from "rxjs";                                                                                                                    
    5: import { Router } from "@angular/router";                                                                                                                                                                   
                                                                                                                                                                                                                   
  • Lines 1–5: Imports Angular HTTP types, dependency injection, routing, and RxJS operators for asynchronous error handling and chaining.                                                                         
                                                                                                                                                                                                                   
    7: let isRefreshing = false;                                                                                                                                                                                   
    8: const refreshComplete$ = new BehaviorSubject<boolean>(false);                                                                                                                                               
                                                                                                                                                                                                                   
  • Line 7 (isRefreshing): A global flag indicating whether a token refresh request is currently in flight. Prevents sending multiple refresh requests at the same time.                                           
  • Line 8 (refreshComplete$): A BehaviorSubject that notifies queued requests whether the token refresh has finished.                                                                                             
  ──────                                                                                                                                                                                                           
  #### Interceptor Setup                                                                                                                                                                                           
                                                                                                                                                                                                                   
    10: export const errorInterceptor: HttpInterceptorFn = (req, next) => {                                                                                                                                        
    11:     const authService = inject(Auth);                                                                                                                                                                      
    12:     const router = inject(Router);                                                                                                                                                                         
                                                                                                                                                                                                                   
  • Line 10: Defines the interceptor function. It takes req (the outgoing HttpRequest) and next (HttpHandlerFn, the next handler in the chain).                                                                    
  • Lines 11–12: Injects auth.ts:10 and Router.                                                                                                                                                                    
  ──────                                                                                                                                                                                                           
  #### Intercepting and Filtering Errors                                                                                                                                                                           
                                                                                                                                                                                                                   
    14:   return next(req).pipe(                                                                                                                                                                                   
    15:     catchError((error: unknown) => {                                                                                                                                                                       
    16:         if(error instanceof HttpErrorResponse && error.status === 401 && !req.url.includes("/api/login_check") && !req.url.includes("/api/token/refresh")) {                                               
                                                                                                                                                                                                                   
  • Line 14: next(req) executes the HTTP request.                                                                                                                                                                  
  • Line 15 (catchError): Intercepts any error thrown by the request.                                                                                                                                              
  • Line 16: Checks four conditions:                                                                                                                                                                               
      1. Is it an HttpErrorResponse?                                                                                                                                                                               
      2. Is the HTTP status 401 Unauthorized?                                                                                                                                                                      
      3. Is the URL not /api/login_check? (If login itself fails with 401, it's bad credentials, not an expired token).                                                                                            
      4. Is the URL not /api/token/refresh? (Prevents an infinite loop if the refresh token itself is expired or invalid).                                                                                         
                                                                                                                                                                                                                   
  ──────                                                                                                                                                                                                           
  #### Case A: First request with 401 (!isRefreshing)                                                                                                                                                              
                                                                                                                                                                                                                   
    17:             if(!isRefreshing) {                                                                                                                                                                            
    18:                 isRefreshing = true;                                                                                                                                                                       
    19:                 refreshComplete$.next(false);                                                                                                                                                              
                                                                                                                                                                                                                   
  • Line 17: Enters here if no other request is currently refreshing the token.                                                                                                                                    
  • Line 18: Sets isRefreshing = true to lock other requests out of triggering redundant refresh calls.                                                                                                            
  • Line 19: Emits false to indicate the refresh process has started and is not yet complete.                                                                                                                      
                                                                                                                                                                                                                   
    21:                 return authService.refreshToken().pipe(                                                                                                                                                    
    22:                     switchMap(() => {                                                                                                                                                                      
    23:                         isRefreshing = false;                                                                                                                                                              
    24:                         refreshComplete$.next(true);                                                                                                                                                       
    25:                         return next(req);                                                                                                                                                                  
    26:                     }),                                                                                                                                                                                    
                                                                                                                                                                                                                   
  • Line 21: Calls auth.ts:27.                                                                                                                                                                                     
  • Line 22 (switchMap): When the refresh succeeds:                                                                                                                                                                
      • Line 23: Unlocks the flag (isRefreshing = false).                                                                                                                                                          
      • Line 24: Emits true on refreshComplete$ to notify all queued requests that the new token is active.                                                                                                        
      • Line 25: Re-executes the original request with next(req) and returns its response.                                                                                                                         
                                                                                                                                                                                                                   
                                                                                                                                                                                                                   
    27:                     catchError((refreshError) => {                                                                                                                                                         
    28:                         isRefreshing = false;                                                                                                                                                              
    29:                         authService.logout();                                                                                                                                                              
    30:                         router.navigate(["/login"]);                                                                                                                                                       
    31:                         return throwError(() => refreshError);                                                                                                                                             
    32:                     })                                                                                                                                                                                     
    33:                 );                                                                                                                                                                                         
                                                                                                                                                                                                                   
  • Lines 27–32 (catchError): If the refresh request itself fails (e.g. refresh token expired or invalid):                                                                                                         
      • Line 28: Resets isRefreshing = false.                                                                                                                                                                      
      • Line 29: Calls authService.logout() to clear auth status / cookies.                                                                                                                                        
      • Line 30: Redirects the user to /login.                                                                                                                                                                     
      • Line 31: Re-throws the refresh error.                                                                                                                                                                      
                                                                                                                                                                                                                   
  ──────                                                                                                                                                                                                           
  #### Case B: Subsequent concurrent requests with 401 (else)                                                                                                                                                      
                                                                                                                                                                                                                   
    34:             } else {                                                                                                                                                                                       
    35:                 return refreshComplete$.pipe(                                                                                                                                                              
    36:                     filter((done) => done !== null),                                                                                                                                                       
    37:                     take(1),                                                                                                                                                                               
    38:                     switchMap((success) => success ? next(req) : throwError(() => new Error("Session expired.")))                                                                                          

Resume with -c (or command below):
agy --conversation=9465349d-7aa7-4dea-88d3-77b67a3b852e

    39:                 )
    40:             }            
  
  • Line 34: If another request gets a 401 while isRefreshing is already true, it enters this branch.
  • Lines 35–37: Subscribes to refreshComplete$ and waits for the refresh result.
  • Line 38 (switchMap):
      • If success is true: Re-runs the pending request using next(req).
      • If success is false: Throws a Session expired. error.
  
  ──────
  #### Passthrough for other errors
  
    41:         }
    42:         return throwError(() => error);
    43:     })
    44:   );
    45: };
  
  • Line 42: If the error was not a 401 (e.g. 404, 500) or was from /api/login_check / /api/token/refresh, it simply re-throws the error to let the caller handle it.
  ──────
  ### ⚠️ Important Observation on Line 36
  
  In error-interceptor.ts:36:
  
    filter((done) => done !== null)
  
  Because refreshComplete$ is initialized as new BehaviorSubject<boolean>(false) and reset to false on line 19, any queued request subscribing in the else block receives false immediately (since false !== null  
  evaluates to true).
  
  As a result, concurrent requests would immediately jump to line 38 with success = false and fail with "Session expired." before the refresh completes.
  
  To make the queue wait properly until the refresh completes, line 36 should filter for filter(done => done === true) (or use BehaviorSubject<boolean | null>(null) and filter(done => done !== null)). 

*/