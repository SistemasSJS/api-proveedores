export const environment = {
  production: true,
  
  // API Configuration for Production
  apiUrl: 'https://api.sjsconstrucciones.com/api',
  
  // API Key for X-API-KEY header authentication
  // This should match the API_REGISTRATION_KEY in your Laravel production .env file
  // IMPORTANT: In production, consider using environment variables or secure configuration management
  apiKey: 'your-production-api-key-here',
  
  // Other environment-specific configurations
  appName: 'SJS Construcciones - Proveedores',
  appVersion: '1.0.0',
  
  // Feature flags
  features: {
    enableDebugMode: false,
    enableLogging: false,
    enableAnalytics: true
  },
  
  // Rate limiting configuration (optional)
  rateLimit: {
    maxRequests: 500,
    windowMs: 60000 // 1 minute
  },
  
  // File upload limits
  fileUpload: {
    maxSizeMB: 25,
    allowedExtensions: ['pdf', 'xml', 'jpg', 'jpeg', 'png']
  }
};

/*
 * SECURITY NOTICE:
 * In production environments, API keys should not be hardcoded in the source code.
 * Consider these alternatives:
 * 
 * 1. Environment Variables during build:
 *    - Use Angular CLI's environment substitution during the build process
 *    - Pass the API key as a build argument: ng build --configuration=production --api-key=$API_KEY
 * 
 * 2. Configuration Service:
 *    - Load configuration from a secure endpoint at application startup
 *    - Store sensitive data in a secure configuration management system
 * 
 * 3. Backend Proxy:
 *    - Route API calls through your backend server
 *    - Let the backend handle API key authentication
 * 
 * 4. Azure Key Vault / AWS Secrets Manager:
 *    - Use cloud provider's secret management services
 *    - Retrieve secrets at runtime through secure APIs
 */