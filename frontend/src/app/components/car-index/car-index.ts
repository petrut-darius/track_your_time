import { Component, inject, OnInit, signal } from '@angular/core';
import { SearchInput } from '../search-input/search-input';
import { Pagination } from '../pagination/pagination';
import { Car } from '../../services/car';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { CarResponse } from '../../models/car-response';
import { PaginationMeta } from '../../models/paginated-response';

@Component({
  selector: 'app-car-index',
  imports: [SearchInput, Pagination, RouterLink],
  templateUrl: './car-index.html',
  styleUrl: './car-index.css',
})
export class CarIndex implements OnInit{
  private carService = inject(Car);
  private route = inject(ActivatedRoute);
  private router = inject(Router);

  cars = signal<CarResponse[]>([]);
  pagination = signal<PaginationMeta>({page: 1, limit: 10, total: 0, totalPages: 0});
  searchQuery = signal<string>("");
  loading = signal<boolean>(false);

  ngOnInit(): void {
    //whenever the route changes this.route.queryParamMap emits the new params set via onSearch or onPageChange
    this.route.queryParamMap.subscribe(params => {
      const page = Number(params.get("page")) || 1;
      const q = params.get("q") || "";

      this.searchQuery.set(q);
      this.loadCars(page, q);
    });
  }

  loadCars(page: number, query: string): void {
    this.loading.set(true);
    this.carService.getCars(page, 10, query).subscribe({
      next: (response) => {
        this.cars.set(response.data);
        this.pagination.set(response.pagination);
        this.loading.set(false);
      },
      error: () => this.loading.set(false),
    });
  }

  onSearch(query: string): void {
    this.router.navigate([], {
      relativeTo: this.route,
      queryParams: {q: query || null, page: 1},
      queryParamsHandling: "merge",
    });
  }


  //page -> is the value set in pagination.html on click
  onPageChange(page: number): void {
    this.router.navigate([], {
      relativeTo: this.route,
      queryParams: {page},
      queryParamsHandling: "merge",
    })
  }

}
