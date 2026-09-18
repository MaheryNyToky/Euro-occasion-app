import 'package:flutter/material.dart';

abstract final class AppColors {
  static const primary = Color(0xFF0F766E);
  static const primaryLight = Color(0xFFE6F5F2);
  static const sidebar = Color(0xFF142235);
  static const sidebarSelected = Color(0xFF24415A);
  static const background = Color(0xFFF5F7FA);
  static const border = Color(0xFFE6EAF0);
  static const muted = Color(0xFF667085);
  static const text = Color(0xFF172B4D);
  static const warning = Color(0xFFF59E0B);
}

abstract final class AppTheme {
  static ThemeData light() {
    final scheme = ColorScheme.fromSeed(seedColor: AppColors.primary);
    return ThemeData(
      useMaterial3: true,
      colorScheme: scheme.copyWith(primary: AppColors.primary),
      scaffoldBackgroundColor: AppColors.background,
      fontFamily: 'Arial',
      textTheme: const TextTheme(
        headlineMedium: TextStyle(color: AppColors.text, fontWeight: FontWeight.w700),
        titleLarge: TextStyle(color: AppColors.text, fontWeight: FontWeight.w700),
        titleMedium: TextStyle(color: AppColors.text, fontWeight: FontWeight.w600),
        bodyMedium: TextStyle(color: AppColors.text),
      ),
      cardTheme: CardThemeData(color: Colors.white, elevation: 0, shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)), margin: EdgeInsets.zero),
      inputDecorationTheme: InputDecorationTheme(border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)), enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: const BorderSide(color: AppColors.border))),
    );
  }
}
