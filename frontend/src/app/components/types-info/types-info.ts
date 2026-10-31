import { Component, inject, signal } from '@angular/core';
import { Types } from '../../services/types';
import { catchError, of, tap } from 'rxjs';
import { HttpErrorResponse } from '@angular/common/http';
import { AsyncPipe } from '@angular/common';
import { RouterLink } from '@angular/router';

@Component({
  selector: 'app-types-info',
  imports: [AsyncPipe, RouterLink],
  templateUrl: './types-info.html',
  styleUrl: './types-info.css',
})
export class TypesInfo {
  private typeService = inject(Types);

  protected expanded = signal<ReadonlySet<string>>(new Set());//a Set() is a collection that only has unique values (nu te gandi ca la un associative array mai degraba ca la un tablou bidimensional)

  readonly scrapedSite = "https://www.racingcircuits.info/";

  types$ = this.typeService.getAll().pipe(
    catchError((err: HttpErrorResponse) => {
      console.log("Failed to load types", err.status, err.message);
      return of(null);
    })
  )

  toggleCircuits(gradeName: string): void {
    this.expanded.update(current => {
      const next = new Set(current);//Set(current) - copie a lui expanded
      next.has(gradeName) ? next.delete(gradeName) : next.add(gradeName);
      return next;
    });
  }
}
