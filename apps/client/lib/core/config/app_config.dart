class AppConfig {
  static const String appName = 'Eurocasion';
  static const String appVersion = '1.0.0';

  /// L'URL de l'API peut être injectée au build via :
  ///   flutter run --dart-define="API_BASE_URL=http://127.0.0.1:8000/api/v1"
  /// Sans cette variable, la valeur par défaut est utilisée.
  static const String defaultApiBaseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'http://127.0.0.1:8000/api/v1',
  );
}
