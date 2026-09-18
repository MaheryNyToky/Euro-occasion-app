import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import 'package:eurocasion_client/features/catalog/presentation/catalog_page.dart';

void main() {
  testWidgets('affiche les onglets du catalogue et l\'en-tete', (WidgetTester tester) async {
    tester.view.physicalSize = const Size(1280, 800);
    tester.view.devicePixelRatio = 1.0;
    addTearDown(tester.view.resetPhysicalSize);

    await tester.pumpWidget(
      const MaterialApp(
        home: Scaffold(
          body: CatalogPage(),
        ),
      ),
    );

    // Initial header & tabs
    expect(find.text('Catalogue'), findsOneWidget);
    expect(find.text('Gérez vos produits et vos référentiels métier connectés en temps réel.'), findsOneWidget);
    expect(find.text('Produits'), findsOneWidget);
    expect(find.text('Catégories'), findsOneWidget);
    expect(find.text('Unités'), findsOneWidget);
    expect(find.text('Fournisseurs'), findsOneWidget);
    expect(find.text('Clients'), findsOneWidget);

    // Initial loading indicator
    expect(find.byType(CircularProgressIndicator), findsOneWidget);

    // Switch tab to Catégories
    await tester.tap(find.text('Catégories'));
    await tester.pump();

    expect(find.text('Catégories'), findsOneWidget);
  });
}
