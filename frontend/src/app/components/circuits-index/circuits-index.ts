import { Component, inject } from '@angular/core';
import { Circuit } from '../../services/circuit';

@Component({
  selector: 'app-circuits-index',
  imports: [],
  templateUrl: './circuits-index.html',
  styleUrl: './circuits-index.css',
})
export class CircuitsIndex {
  private circuitService = inject(Circuit);

//  circuits$ = this.circuitService.get
}
