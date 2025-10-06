// This file can be replaced during build by using the `fileReplacements` array.
// `ng build` replaces `environment.ts` with `environment.prod.ts`.
// The list of file replacements can be found in `angular.json`.

export const environment = {
  production: false,
  
  // API Configuration
  apiUrl: 'http://localhost:8000/api',
  
  // API Key for X-API-KEY header authentication
  // This should match the API_REGISTRATION_KEY in your Laravel .env file
  apiKey: 'your-development-api-key-here',
  
  // Other environment-specific configurations
  appName: 'SJS Construcciones - Proveedores',
  appVersion: '1.0.0',
  
  // Feature flags
  features: {
    enableDebugMode: true,
    enableLogging: true,
    enableAnalytics: false
  },
  
  // Rate limiting configuration (optional)
  rateLimit: {
    maxRequests: 100,
    windowMs: 60000 // 1 minute
  },
  
  // File upload limits
  fileUpload: {
    maxSizeMB: 10,
    allowedExtensions: ['pdf', 'xml', 'jpg', 'jpeg', 'png']
  }
};

/*
 * For easier debugging in development mode, you can import the following file
 * to ignore zone related error stack frames such as `zone.run`, `zoneDelegate.invokeTask`.
 *
 * This import should be commented out in production mode because it will have a negative impact
 * on performance if an error is thrown.
 */
// import 'zone.js/plugins/zone-error';  // Included with Angular CLI.