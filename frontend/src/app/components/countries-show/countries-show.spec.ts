import { ComponentFixture, TestBed } from '@angular/core/testing';

import { CountriesShow } from './countries-show';

describe('CountriesShow', () => {
  let component: CountriesShow;
  let fixture: ComponentFixture<CountriesShow>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [CountriesShow],
    }).compileComponents();

    fixture = TestBed.createComponent(CountriesShow);
    component = fixture.componentInstance;
    await fixture.whenStable();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
