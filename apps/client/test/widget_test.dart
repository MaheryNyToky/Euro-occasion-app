import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import 'package:eurocasion_client/app.dart';

void main() {
  testWidgets('affiche l\'ecran de connexion par defaut', (WidgetTester tester) async {
    await tester.pumpWidget(const EurocasionApp());

    expect(find.text('Eurocasion ERP'), findsOneWidget);
    expect(find.text('Gestion de stock, importations et caisse'), findsOneWidget);
    expect(find.text('Code Entreprise'), findsOneWidget);
    expect(find.text('Adresse e-mail'), findsOneWidget);
    expect(find.text('Mot de passe'), findsOneWidget);
    expect(find.text('Se connecter'), findsOneWidget);
  });

  testWidgets('affiche le shell et tableau de bord une fois connecte', (WidgetTester tester) async {
    // Set a wide surface size to simulate desktop view
    tester.view.physicalSize = const Size(1280, 800);
    tester.view.devicePixelRatio = 1.0;
    addTearDown(tester.view.resetPhysicalSize);

    await tester.pumpWidget(
      const MaterialApp(
        home: AppShell(),
      ),
    );

    expect(find.text('Tableau de bord'), findsAtLeastNWidgets(1));
    expect(find.text('Actions rapides'), findsOneWidget);
    expect(find.text('Catalogue'), findsOneWidget);
    expect(find.text('Stock'), findsOneWidget);
    expect(find.text('Ventes'), findsOneWidget);
  });
}
