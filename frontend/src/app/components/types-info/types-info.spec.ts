import { ComponentFixture, TestBed } from '@angular/core/testing';

import { TypesInfo } from './types-info';

describe('TypesInfo', () => {
  let component: TypesInfo;
  let fixture: ComponentFixture<TypesInfo>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [TypesInfo],
    }).compileComponents();

    fixture = TestBed.createComponent(TypesInfo);
    component = fixture.componentInstance;
    await fixture.whenStable();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
