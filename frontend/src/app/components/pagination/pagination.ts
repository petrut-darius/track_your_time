import { Component, computed, input, output } from '@angular/core';

@Component({
  selector: 'app-pagination',
  imports: [],
  templateUrl: './pagination.html',
  styleUrl: './pagination.css',
})
export class Pagination {
  currentPage = input.required<number>();
  totalPages = input.required<number>();

  pageChange = output<number>();

  pages = computed(() => {
    const total = this.totalPages();
    const current = this.currentPage();

    const delta = 2;
    const start = Math.max(1, current - delta);
    const end = Math.min(total, current + delta);
    
    const list: number[] = [];
    for(let i = start; i <= end; i++) {
      list.push(i);
    }
    return list;
  });

  goToPage(page: number): void {
    if(page >= 1 && page <= this.totalPages() && page !== this.currentPage()) {
      this.pageChange.emit(page);
    }
  }
}
