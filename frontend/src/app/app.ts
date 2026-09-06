import { Component, OnInit, signal } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { RouterOutlet } from '@angular/router';
import { Navbar } from './components/navbar/navbar';
import { Footer } from './components/footer/footer';

@Component({
  selector: 'app-root',
  imports: [RouterOutlet, Navbar, Footer],
  templateUrl: './app.html',
  styleUrl: './app.css'
})
export class App implements OnInit {
  message = signal('');

  constructor(private http: HttpClient) {}

  ngOnInit() {
    this.http.get<{ message: string }>('/api/test')
      .subscribe({
        next: (res) => this.message.set(res.message),
        error: (err) => console.error('API call failed:', err)
      });
  }
}