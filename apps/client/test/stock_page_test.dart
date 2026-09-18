import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import 'package:eurocasion_client/features/stock/presentation/stock_page.dart';

void main() {
  testWidgets('affiche la page Stock & Entrées et ouvre le modal d\'ajout avec champs corrigés', (WidgetTester tester) async {
    tester.view.physicalSize = const Size(1280, 800);
    tester.view.devicePixelRatio = 1.0;
    addTearDown(tester.view.resetPhysicalSize);

    await tester.pumpWidget(
      const MaterialApp(
        home: Scaffold(
          body: StockPage(),
        ),
      ),
    );

    // Verify Stock header & action button
    expect(find.text('Stock & Entrées'), findsOneWidget);
    expect(find.text('Nouveau produit / Entrée stock'), findsOneWidget);
    expect(find.text('Total pièces en stock'), findsOneWidget);
    expect(find.text('Références actives'), findsOneWidget);
    expect(find.text('Articles en rupture'), findsOneWidget);

    // Open add stock dialog
    await tester.tap(find.text('Nouveau produit / Entrée stock'));
    await tester.pumpAndSettle();

    // Verify corrected fields
    expect(find.text('Ajouter un produit au stock'), findsOneWidget);
    expect(find.text('Code SKU : Généré automatiquement à l\'enregistrement.'), findsOneWidget);
    expect(find.text('Nom du produit *'), findsOneWidget);
    expect(find.text('Fabricant'), findsOneWidget);
    expect(find.text('Catégorie (saisie libre ou existante)'), findsOneWidget);
    expect(find.text('Quantité reçue en stock *'), findsOneWidget);

    // Verify observation field
    expect(find.text('Observation (facultatif)'), findsOneWidget);

    // Verify "seuil alerte" is NOT present
    expect(find.text('Seuil d\'alerte stock'), findsNothing);
    expect(find.text('Référence fabricant'), findsNothing);

    // Select "Carton" in Unit dropdown
    await tester.tap(find.text('Pièce (PCE)'));
    await tester.pumpAndSettle();
    await tester.tap(find.text('Carton (CRT)').last);
    await tester.pumpAndSettle();

    // Verify Carton custom packaging field appears
    expect(find.text('Nombre de pièces par carton *'), findsOneWidget);
    expect(find.textContaining('Équivalence :'), findsOneWidget);
    expect(find.text('Nombre de cartons reçus *'), findsOneWidget);
  });
}

