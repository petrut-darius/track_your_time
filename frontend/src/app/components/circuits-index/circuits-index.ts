import { Component, inject, OnInit, signal } from '@angular/core';
import { Circuit } from '../../services/circuit';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { CircuitResponse } from '../../models/circuit-response';
import { PaginationMeta } from '../../models/paginated-response';
import { SearchInput } from '../search-input/search-input';
import { Pagination } from '../pagination/pagination';

@Component({
  selector: 'app-circuits-index',
  imports: [SearchInput, Pagination, RouterLink],
  templateUrl: './circuits-index.html',
  styleUrl: './circuits-index.css',
})
export class CircuitsIndex implements OnInit{
  private circuitService = inject(Circuit);
  private route = inject(ActivatedRoute);
  private router = inject(Router);

  circuits = signal<CircuitResponse[]>([]);
  pagination = signal<PaginationMeta>({page: 1, limit: 10, total: 0, totalPages: 0});
  searchQuery = signal<string>("");
  loading = signal<boolean>(false); 

  ngOnInit(): void {
    this.route.queryParamMap.subscribe(params => {
      const page = Number(params.get("page")) || 1;
      const q = params.get("q") || "";

      this.searchQuery.set(q);
      this.loadCircuits(page, q);
    });
  }

  loadCircuits(page: number, query: string): void {
    this.loading.set(true);
    this.circuitService.getCircuits(page, 10, query).subscribe({
      next: (res) => {
        this.circuits.set(res.data);
        this.pagination.set(res.pagination);
        this.loading.set(false);
      },
      error: () => this.loading.set(false),
    })
  }

  onSearch(query: string): void {
    this.router.navigate([], {
      relativeTo: this.route,
      queryParams: {q: query || null, page: 1},
      queryParamsHandling: "merge",
    });
  }

  onPageChange(page: number): void {
    this.router.navigate([], {
      relativeTo: this.route,
      queryParams: {page},
      queryParamsHandling: "merge",
    })
  }
}
