import { NgModule } from '@angular/core';
import { BrowserModule } from '@angular/platform-browser';
import { HttpClientModule } from '@angular/common/http';
import { BrowserAnimationsModule } from '@angular/platform-browser/animations';

// Import the service with API Key authentication
import { ConstruccSolicitudesPagoApiKeyService } from '../services/construcc-solicitudes-pago-apikey.service';

// Import other services if needed
import { ConstruccSolicitudesPagoService } from '../services/construcc-solicitudes-pago.service';

// Import your components
import { AppComponent } from './app.component';
import { SolicitudesPagoListComponent } from './components/solicitudes-pago-list/solicitudes-pago-list.component';
import { SolicitudPagoDetailComponent } from './components/solicitud-pago-detail/solicitud-pago-detail.component';
import { SolicitudPagoStatusComponent } from './components/solicitud-pago-status/solicitud-pago-status.component';

// Import routing module
import { AppRoutingModule } from './app-routing.module';

// Import Angular Material modules (optional)
import { MatTableModule } from '@angular/material/table';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { MatSnackBarModule } from '@angular/material/snack-bar';
import { MatDialogModule } from '@angular/material/dialog';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatSelectModule } from '@angular/material/select';
import { MatDatepickerModule } from '@angular/material/datepicker';
import { MatNativeDateModule } from '@angular/material/core';
import { MatPaginatorModule } from '@angular/material/paginator';
import { MatSortModule } from '@angular/material/sort';
import { MatCardModule } from '@angular/material/card';
import { MatChipsModule } from '@angular/material/chips';
import { MatTooltipModule } from '@angular/material/tooltip';

// Import forms modules
import { FormsModule, ReactiveFormsModule } from '@angular/forms';

@NgModule({
  declarations: [
    AppComponent,
    SolicitudesPagoListComponent,
    SolicitudPagoDetailComponent,
    SolicitudPagoStatusComponent,
    // Add other components here
  ],
  imports: [
    // Angular Core Modules
    BrowserModule,
    HttpClientModule, // REQUIRED for HTTP services
    BrowserAnimationsModule,
    FormsModule,
    ReactiveFormsModule,
    
    // Routing
    AppRoutingModule,
    
    // Angular Material Modules (optional)
    MatTableModule,
    MatButtonModule,
    MatIconModule,
    MatProgressSpinnerModule,
    MatSnackBarModule,
    MatDialogModule,
    MatFormFieldModule,
    MatInputModule,
    MatSelectModule,
    MatDatepickerModule,
    MatNativeDateModule,
    MatPaginatorModule,
    MatSortModule,
    MatCardModule,
    MatChipsModule,
    MatTooltipModule,
  ],
  providers: [
    // Register the API Key service as a provider
    ConstruccSolicitudesPagoApiKeyService,
    
    // You can also provide it with a custom configuration
    // {
    //   provide: ConstruccSolicitudesPagoApiKeyService,
    //   useFactory: (http: HttpClient) => {
    //     return new ConstruccSolicitudesPagoApiKeyService(http);
    //   },
    //   deps: [HttpClient]
    // },
    
    // If you need both services (Bearer token and API Key)
    ConstruccSolicitudesPagoService,
    
    // Add other services here
  ],
  bootstrap: [AppComponent]
})
export class AppModule { }

/**
 * ALTERNATIVE: Using a Core Module for Services
 * 
 * If you prefer to organize services in a separate module:
 */

// core.module.ts
import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { HttpClientModule } from '@angular/common/http';

// Import all your services
import { ConstruccSolicitudesPagoApiKeyService } from '../services/construcc-solicitudes-pago-apikey.service';

@NgModule({
  imports: [
    CommonModule,
    HttpClientModule
  ],
  providers: [
    ConstruccSolicitudesPagoApiKeyService,
    // Add other services here
  ]
})
export class CoreModule { }

/**
 * ALTERNATIVE: Using providedIn: 'root' (Recommended)
 * 
 * The service is already configured with providedIn: 'root' in the @Injectable decorator,
 * which means it's automatically available throughout the application without needing to
 * add it to the providers array. This is the recommended approach for Angular 6+.
 * 
 * @Injectable({
 *   providedIn: 'root',  // <-- This makes the service available application-wide
 * })
 * export class ConstruccSolicitudesPagoApiKeyService { ... }
 * 
 * With this configuration, you don't need to add the service to any providers array.
 * Just import and inject it where needed:
 * 
 * constructor(private solicitudService: ConstruccSolicitudesPagoApiKeyService) { }
 */

/**
 * ENVIRONMENT-BASED SERVICE SELECTION
 * 
 * If you want to switch between API Key and Bearer token authentication based on environment:
 */

// app.module.ts with environment-based provider
import { environment } from '../environments/environment';

export const SOLICITUD_SERVICE_PROVIDER = {
  provide: 'ISolicitudPagoService',
  useClass: environment.features.useApiKey 
    ? ConstruccSolicitudesPagoApiKeyService 
    : ConstruccSolicitudesPagoService,
  deps: [HttpClient]
};

// Then in your module:
// providers: [
//   SOLICITUD_SERVICE_PROVIDER,
//   // other providers...
// ]

// And inject in components like this:
// constructor(@Inject('ISolicitudPagoService') private solicitudService: any) { }