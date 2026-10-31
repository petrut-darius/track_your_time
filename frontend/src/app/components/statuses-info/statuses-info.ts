import { Component, inject, signal } from '@angular/core';
import { Statuses } from '../../services/statuses';
import { catchError, of, tap } from 'rxjs';
import { HttpErrorResponse } from '@angular/common/http';
import { AsyncPipe } from '@angular/common';
import { RouterLink } from '@angular/router';

@Component({
  selector: 'app-statuses-info',
  imports: [AsyncPipe, RouterLink],
  templateUrl: './statuses-info.html',
  styleUrl: './statuses-info.css',
})
export class StatusesInfo {
  private statusService = inject(Statuses);

  protected expanded = signal<ReadonlySet<string>>(new Set());//a Set() is a collection that only has unique values (nu te gandi ca la un associative array mai degraba ca la un tablou bidimensional)

  readonly scrapedSite = "https://www.racingcircuits.info/";

  statuses$ = this.statusService.getAll().pipe(
    catchError((err: HttpErrorResponse) => {
      console.log("Failed to load statuses", err.status, err.message);
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
