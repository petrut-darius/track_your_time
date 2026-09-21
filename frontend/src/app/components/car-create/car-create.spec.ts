import { ComponentFixture, TestBed } from '@angular/core/testing';

import { CarCreate } from './car-create';

describe('CarCreate', () => {
  let component: CarCreate;
  let fixture: ComponentFixture<CarCreate>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [CarCreate],
    }).compileComponents();

    fixture = TestBed.createComponent(CarCreate);
    component = fixture.componentInstance;
    await fixture.whenStable();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
