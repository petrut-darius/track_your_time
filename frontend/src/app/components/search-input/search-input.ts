import { Component, DestroyRef, inject, input, OnInit, output } from '@angular/core';
import { FormControl, ReactiveFormsModule } from '@angular/forms';
import { debounceTime, distinctUntilChanged } from 'rxjs';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';      

@Component({
  selector: 'app-search-input',
  imports: [ReactiveFormsModule],
  templateUrl: './search-input.html',
  styleUrl: './search-input.css',
})
export class SearchInput implements OnInit {
  private destroyRef = inject(DestroyRef);

  placeholder = input<string>("Search...");
  debounceMs = input<number>(350);
  initialValue = input<string>("");

  searchChange = output<string>();

  searchControl = new FormControl("");

  ngOnInit(): void {
    if(this.initialValue()) {
      this.searchControl.setValue(this.initialValue(), {emitEvent: false});
    }

    this.searchControl.valueChanges.pipe(
      debounceTime(this.debounceMs()),
      distinctUntilChanged(),
      takeUntilDestroyed(this.destroyRef)
    ).subscribe(value => {
      this.searchChange.emit(value ?? "");
    });
  }

  clear(): void {
    this.searchControl.setValue("");
  }
}
