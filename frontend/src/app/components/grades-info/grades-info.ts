import { Component, inject, signal } from '@angular/core';
import { Grades } from '../../services/grades';
import { catchError, of, tap } from 'rxjs';
import { HttpErrorResponse } from '@angular/common/http';
import { AsyncPipe } from '@angular/common';
import { RouterLink } from '@angular/router';

@Component({
  selector: 'app-grades-info',
  imports: [AsyncPipe, RouterLink],
  templateUrl: './grades-info.html',
  styleUrl: './grades-info.css',
})
export class GradesInfo {
  private gradeService = inject(Grades);

  protected expanded = signal<ReadonlySet<string>>(new Set());//a Set() is a collection that only has unique values (nu te gandi ca la un associative array mai degraba ca la un tablou bidimensional)

  readonly scrapedSite = "https://www.racingcircuits.info/";

  grades$ = this.gradeService.getAll().pipe(
    catchError((err: HttpErrorResponse) => {
      console.log("Failed to load grades", err.status, err.message);
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
