import 'package:flutter/material.dart';

import '../../../core/theme/app_theme.dart';
import '../../catalog/models/catalog_models.dart';
import '../../catalog/services/catalog_service.dart';

class StockPage extends StatefulWidget {
  const StockPage({super.key});

  @override
  State<StockPage> createState() => _StockPageState();
}

class _StockPageState extends State<StockPage> {
  List<ProductModel> _products = [];
  List<CategoryModel> _categories = [];
  List<UnitModel> _units = [];

  bool _isLoading = false;
  String? _errorMessage;
  final _searchController = TextEditingController();
  String? _stateFilter;

  // Pre-configured suggestions for product names and manufacturers
  static const _productSuggestions = [
    'iPhone 11 64Go',
    'iPhone 12 128Go',
    'iPhone 13 128Go',
    'iPhone 14 Pro 128Go',
    'iPhone 15 Pro Max 256Go',
    'Samsung Galaxy S22 128Go',
    'Samsung Galaxy S23 Ultra',
    'Dell Latitude 5420 Core i7',
    'Dell Latitude 7490 Core i5',
    'HP EliteBook 840 G8',
    'Lenovo ThinkPad T14',
    'MacBook Air M1 256Go',
    'MacBook Pro M2 512Go',
    'Câble USB-C Power Delivery 100W 2m',
    'Adaptateur Secteur 65W Fast Charge',
  ];

  static const _manufacturerSuggestions = [
    'Apple',
    'Dell',
    'HP',
    'Lenovo',
    'Samsung',
    'Asus',
    'Acer',
    'Sony',
    'Baseus',
    'Xiaomi',
    'Huawei',
    'Toyota',
    'Bosch',
  ];

  @override
  void initState() {
    super.initState();
    _loadStockData();
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  Future<void> _loadStockData() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      final res = await Future.wait([
        CatalogService.instance.getProducts(
          search: _searchController.text.trim(),
          state: _stateFilter,
        ),
        CatalogService.instance.getCategories(),
        CatalogService.instance.getUnits(),
      ]);

      if (mounted) {
        setState(() {
          _products = res[0] as List<ProductModel>;
          _categories = res[1] as List<CategoryModel>;
          _units = res[2] as List<UnitModel>;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() => _errorMessage = e.toString());
      }
    } finally {
      if (mounted) {
        setState(() => _isLoading = false);
      }
    }
  }

  void _showAddStockDialog() {
    final availableUnits = _units.isNotEmpty
        ? _units
        : [
            UnitModel(id: 'default-pce', code: 'PCE', name: 'Pièce', precision: 0, isBase: true),
            UnitModel(id: 'default-crt', code: 'CRT', name: 'Carton', precision: 0, isBase: false),
          ];

    String selectedName = '';
    String selectedManufacturer = '';
    String selectedCategoryName = '';
    String selectedState = 'new';
    String? selectedUnitId = availableUnits.first.id;
    final qtyController = TextEditingController(text: '1');
    final piecesPerCartonController = TextEditingController(text: '12');
    final observationController = TextEditingController();
    final formKey = GlobalKey<FormState>();

    // Combine database product names with suggestions
    final allProductSuggestions = <String>{
      ..._products.map((p) => p.name),
      ..._productSuggestions,
    }.toList();

    final allCategorySuggestions = _categories.map((c) => c.name).toList();

    showDialog(
      context: context,
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, setDialogState) {
          final selectedUnit = availableUnits.firstWhere(
            (u) => u.id == selectedUnitId,
            orElse: () => availableUnits.first,
          );
          final isCarton = selectedUnit.code.toUpperCase() == 'CRT' ||
              selectedUnit.name.toLowerCase().contains('carton');

          return AlertDialog(
          title: const Row(
            children: [
              Icon(Icons.add_box_outlined, color: AppColors.primary),
              SizedBox(width: 10),
              Text('Ajouter un produit au stock'),
            ],
          ),
          content: SizedBox(
            width: 520,
            child: Form(
              key: formKey,
              child: SingleChildScrollView(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // SKU info notice
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                      decoration: BoxDecoration(
                        color: AppColors.background,
                        borderRadius: BorderRadius.circular(8),
                        border: Border.all(color: AppColors.border),
                      ),
                      child: const Row(
                        children: [
                          Icon(Icons.qr_code_2, size: 20, color: AppColors.primary),
                          SizedBox(width: 10),
                          Expanded(
                            child: Text(
                              'Code SKU : Généré automatiquement à l\'enregistrement.',
                              style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: AppColors.muted),
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 18),

                    // Nom du produit avec autocomplétion
                    const Text('Nom du produit *', style: TextStyle(fontSize: 13, fontWeight: FontWeight.w600)),
                    const SizedBox(height: 6),
                    Autocomplete<String>(
                      optionsBuilder: (textEditingValue) {
                        if (textEditingValue.text.trim().isEmpty) {
                          return const Iterable<String>.empty();
                        }
                        return allProductSuggestions.where(
                          (item) => item.toLowerCase().contains(textEditingValue.text.toLowerCase()),
                        );
                      },
                      onSelected: (selection) {
                        selectedName = selection;
                        // Auto detect manufacturer
                        final lower = selection.toLowerCase();
                        if (lower.contains('iphone') || lower.contains('macbook') || lower.contains('apple')) {
                          selectedManufacturer = 'Apple';
                        } else if (lower.contains('dell')) {
                          selectedManufacturer = 'Dell';
                        } else if (lower.contains('samsung') || lower.contains('galaxy')) {
                          selectedManufacturer = 'Samsung';
                        } else if (lower.contains('thinkpad') || lower.contains('lenovo')) {
                          selectedManufacturer = 'Lenovo';
                        } else if (lower.contains('elitebook') || lower.contains('hp')) {
                          selectedManufacturer = 'HP';
                        }
                        setDialogState(() {});
                      },
                      fieldViewBuilder: (context, controller, focusNode, onFieldSubmitted) {
                        return TextFormField(
                          controller: controller,
                          focusNode: focusNode,
                          decoration: const InputDecoration(
                            hintText: 'Tapez ou sélectionnez un nom (ex: iPhone 14 Pro)',
                            prefixIcon: Icon(Icons.shopping_bag_outlined),
                            border: OutlineInputBorder(),
                            isDense: true,
                          ),
                          onChanged: (val) => selectedName = val,
                          validator: (v) => (v == null || v.trim().isEmpty) ? 'Nom requis' : null,
                        );
                      },
                    ),
                    const SizedBox(height: 14),

                    // Fabricant avec autocomplétion
                    const Text('Fabricant', style: TextStyle(fontSize: 13, fontWeight: FontWeight.w600)),
                    const SizedBox(height: 6),
                    Autocomplete<String>(
                      optionsBuilder: (textEditingValue) {
                        if (textEditingValue.text.trim().isEmpty) {
                          return _manufacturerSuggestions;
                        }
                        return _manufacturerSuggestions.where(
                          (m) => m.toLowerCase().contains(textEditingValue.text.toLowerCase()),
                        );
                      },
                      onSelected: (selection) {
                        selectedManufacturer = selection;
                      },
                      fieldViewBuilder: (context, controller, focusNode, onFieldSubmitted) {
                        if (selectedManufacturer.isNotEmpty && controller.text.isEmpty) {
                          controller.text = selectedManufacturer;
                        }
                        return TextFormField(
                          controller: controller,
                          focusNode: focusNode,
                          decoration: const InputDecoration(
                            hintText: 'Fabricant (ex: Apple, Dell, Samsung...)',
                            prefixIcon: Icon(Icons.business_outlined),
                            border: OutlineInputBorder(),
                            isDense: true,
                          ),
                          onChanged: (val) => selectedManufacturer = val,
                        );
                      },
                    ),
                    const SizedBox(height: 14),

                    // Catégorie avec saisie manuelle et autocomplétion
                    const Text('Catégorie (saisie libre ou existante)', style: TextStyle(fontSize: 13, fontWeight: FontWeight.w600)),
                    const SizedBox(height: 6),
                    Autocomplete<String>(
                      optionsBuilder: (textEditingValue) {
                        if (textEditingValue.text.trim().isEmpty) {
                          return allCategorySuggestions;
                        }
                        return allCategorySuggestions.where(
                          (c) => c.toLowerCase().contains(textEditingValue.text.toLowerCase()),
                        );
                      },
                      onSelected: (selection) {
                        selectedCategoryName = selection;
                      },
                      fieldViewBuilder: (context, controller, focusNode, onFieldSubmitted) {
                        return TextFormField(
                          controller: controller,
                          focusNode: focusNode,
                          decoration: const InputDecoration(
                            hintText: 'Tapez la catégorie (ex: Smartphones, Laptops...)',
                            prefixIcon: Icon(Icons.category_outlined),
                            border: OutlineInputBorder(),
                            isDense: true,
                          ),
                          onChanged: (val) => selectedCategoryName = val,
                        );
                      },
                    ),
                    const SizedBox(height: 14),

                    Row(
                      children: [
                        // État
                        Expanded(
                          child: DropdownButtonFormField<String>(
                            initialValue: selectedState,
                            decoration: const InputDecoration(
                              labelText: 'État du produit',
                              border: OutlineInputBorder(),
                              isDense: true,
                            ),
                            items: const [
                              DropdownMenuItem(value: 'new', child: Text('Neuf')),
                              DropdownMenuItem(value: 'used', child: Text('Occasion')),
                              DropdownMenuItem(value: 'refurbished', child: Text('Reconditionné')),
                              DropdownMenuItem(value: 'damaged', child: Text('Endommagé')),
                            ],
                            onChanged: (v) => setDialogState(() => selectedState = v ?? 'new'),
                          ),
                        ),
                        const SizedBox(width: 12),

                        // Unité de base
                        Expanded(
                          child: DropdownButtonFormField<String>(
                            initialValue: selectedUnitId,
                            decoration: const InputDecoration(
                              labelText: 'Unité *',
                              border: OutlineInputBorder(),
                              isDense: true,
                            ),
                            items: availableUnits
                                .map((u) => DropdownMenuItem(value: u.id, child: Text('${u.name} (${u.code})')))
                                .toList(),
                            onChanged: (v) => setDialogState(() => selectedUnitId = v),
                          ),
                        ),
                      ],
                    ),

                    // Configuration carton personnalisée
                    if (isCarton) ...[
                      const SizedBox(height: 14),
                      TextFormField(
                        controller: piecesPerCartonController,
                        keyboardType: const TextInputType.numberWithOptions(decimal: false),
                        decoration: const InputDecoration(
                          labelText: 'Nombre de pièces par carton *',
                          hintText: 'Ex: 10, 12, 24, 50...',
                          prefixIcon: Icon(Icons.layers_outlined),
                          border: OutlineInputBorder(),
                          isDense: true,
                          helperText: 'Précisez le conditionnement de ce produit',
                        ),
                        onChanged: (_) => setDialogState(() {}),
                        validator: (v) {
                          if (!isCarton) return null;
                          if (v == null || v.trim().isEmpty) return 'Précisez le nombre de pièces';
                          final num = double.tryParse(v.trim());
                          if (num == null || num <= 0) return 'Nombre invalide (> 0)';
                          return null;
                        },
                      ),
                      const SizedBox(height: 10),
                      Builder(
                        builder: (context) {
                          final cartons = double.tryParse(qtyController.text.trim()) ?? 0;
                          final pcs = double.tryParse(piecesPerCartonController.text.trim()) ?? 0;
                          final totalPcs = cartons * pcs;
                          return Container(
                            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                            decoration: BoxDecoration(
                              color: Colors.amber.shade50,
                              borderRadius: BorderRadius.circular(8),
                              border: Border.all(color: Colors.amber.shade300),
                            ),
                            child: Row(
                              children: [
                                Icon(Icons.calculate_outlined, size: 20, color: Colors.amber.shade900),
                                const SizedBox(width: 10),
                                Expanded(
                                  child: Text(
                                    'Équivalence : ${cartons.toStringAsFixed(cartons.truncateToDouble() == cartons ? 0 : 2)} carton(s) × ${pcs.toStringAsFixed(0)} pcs = ${totalPcs.toStringAsFixed(0)} pièces au total',
                                    style: TextStyle(
                                      fontSize: 12,
                                      fontWeight: FontWeight.w600,
                                      color: Colors.brown.shade900,
                                    ),
                                  ),
                                ),
                              ],
                            ),
                          );
                        },
                      ),
                    ],
                    const SizedBox(height: 14),

                    // Quantité
                    TextFormField(
                      controller: qtyController,
                      keyboardType: const TextInputType.numberWithOptions(decimal: true),
                      decoration: InputDecoration(
                        labelText: isCarton ? 'Nombre de cartons reçus *' : 'Quantité reçue en stock *',
                        hintText: isCarton ? 'Ex: 5, 10' : 'Ex: 1, 5, 20',
                        prefixIcon: const Icon(Icons.inventory_outlined),
                        border: const OutlineInputBorder(),
                        isDense: true,
                      ),
                      onChanged: (_) => setDialogState(() {}),
                      validator: (v) {
                        if (v == null || v.trim().isEmpty) return 'Quantité requise';
                        final num = double.tryParse(v.trim());
                        if (num == null || num < 0) return 'Quantité invalide';
                        return null;
                      },
                    ),
                    const SizedBox(height: 14),

                    // Observation facultative
                    TextFormField(
                      controller: observationController,
                      maxLines: 2,
                      decoration: const InputDecoration(
                        labelText: 'Observation (facultatif)',
                        hintText: 'Remarques (état du colis, fournisseur, lot...)',
                        prefixIcon: Icon(Icons.note_alt_outlined),
                        border: OutlineInputBorder(),
                        isDense: true,
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(ctx),
              child: const Text('Annuler'),
            ),
            FilledButton.icon(
              icon: const Icon(Icons.check, size: 18),
              label: const Text('Enregistrer dans le stock'),
              onPressed: () async {
                if (!formKey.currentState!.validate() || selectedUnitId == null) {
                  return;
                }

                Navigator.pop(ctx);
                try {
                  final Map<String, dynamic> payload = {
                    'name': selectedName.trim(),
                    'manufacturer': selectedManufacturer.trim().isNotEmpty ? selectedManufacturer.trim() : null,
                    'category_name': selectedCategoryName.trim().isNotEmpty ? selectedCategoryName.trim() : null,
                    'state': selectedState,
                    'base_unit_id': selectedUnitId,
                    'quantity': double.tryParse(qtyController.text.trim()) ?? 1,
                  };

                  if (observationController.text.trim().isNotEmpty) {
                    payload['observation'] = observationController.text.trim();
                  }

                  if (isCarton) {
                    final pcs = double.tryParse(piecesPerCartonController.text.trim());
                    if (pcs != null && pcs > 0) {
                      payload['pieces_per_carton'] = pcs;
                    }
                  }

                  final createdProduct = await CatalogService.instance.createProduct(payload);

                  if (!mounted) return;
                  ScaffoldMessenger.of(context).showSnackBar(
                    SnackBar(
                      backgroundColor: Colors.green.shade800,
                      content: Text(
                        'Produit "${createdProduct.name}" ajouté avec succès ! SKU généré : ${createdProduct.sku} (Stock : ${createdProduct.totalOnHand.toStringAsFixed(0)})',
                      ),
                    ),
                  );
                  _loadStockData();
                } catch (e) {
                  if (!mounted) return;
                  ScaffoldMessenger.of(context).showSnackBar(
                    SnackBar(content: Text('Erreur: $e'), backgroundColor: Colors.red),
                  );
                }
              },
            ),
          ],
        );
        },
      ),
    );
  }

  Color _getStateColor(String state) {
    switch (state) {
      case 'new':
        return Colors.green;
      case 'used':
        return Colors.amber.shade800;
      case 'refurbished':
        return Colors.blue;
      case 'damaged':
        return Colors.red;
      default:
        return Colors.grey;
    }
  }

  @override
  Widget build(BuildContext context) {
    final totalUnits = _products.fold<double>(0.0, (acc, p) => acc + p.totalOnHand);
    final outOfStockCount = _products.where((p) => p.totalOnHand <= 0).length;

    return SingleChildScrollView(
      padding: const EdgeInsets.all(30),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Header
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('Stock & Entrées', style: TextStyle(fontSize: 28, fontWeight: FontWeight.w700, color: AppColors.text)),
                    SizedBox(height: 6),
                    Text(
                      'Suivi en direct des niveaux de stock physique, réceptions et entrées d\'articles.',
                      style: TextStyle(color: AppColors.muted),
                    ),
                  ],
                ),
              ),
              IconButton(
                tooltip: 'Actualiser',
                icon: const Icon(Icons.refresh),
                onPressed: _isLoading ? null : _loadStockData,
              ),
              const SizedBox(width: 8),
              FilledButton.icon(
                onPressed: _showAddStockDialog,
                icon: const Icon(Icons.add_box_outlined),
                label: const Text('Nouveau produit / Entrée stock'),
              ),
            ],
          ),
          const SizedBox(height: 24),

          // KPI Cards
          Row(
            children: [
              Expanded(
                child: Card(
                  child: Padding(
                    padding: const EdgeInsets.all(18),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text('Total pièces en stock', style: TextStyle(color: AppColors.muted, fontSize: 12)),
                        const SizedBox(height: 6),
                        Text(
                          totalUnits.toStringAsFixed(0),
                          style: const TextStyle(fontSize: 24, fontWeight: FontWeight.bold, color: AppColors.primary),
                        ),
                      ],
                    ),
                  ),
                ),
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Card(
                  child: Padding(
                    padding: const EdgeInsets.all(18),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text('Références actives', style: TextStyle(color: AppColors.muted, fontSize: 12)),
                        const SizedBox(height: 6),
                        Text(
                          '${_products.length}',
                          style: const TextStyle(fontSize: 24, fontWeight: FontWeight.bold, color: AppColors.text),
                        ),
                      ],
                    ),
                  ),
                ),
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Card(
                  child: Padding(
                    padding: const EdgeInsets.all(18),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text('Articles en rupture', style: TextStyle(color: AppColors.muted, fontSize: 12)),
                        const SizedBox(height: 6),
                        Text(
                          '$outOfStockCount',
                          style: TextStyle(
                            fontSize: 24,
                            fontWeight: FontWeight.bold,
                            color: outOfStockCount > 0 ? Colors.red : Colors.green,
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 24),

          // Filters & Table
          Card(
            child: Padding(
              padding: const EdgeInsets.all(22),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Expanded(
                        child: TextField(
                          controller: _searchController,
                          decoration: InputDecoration(
                            hintText: 'Rechercher par nom, fabricant ou SKU...',
                            prefixIcon: const Icon(Icons.search),
                            suffixIcon: _searchController.text.isNotEmpty
                                ? IconButton(
                                    icon: const Icon(Icons.clear, size: 18),
                                    onPressed: () {
                                      _searchController.clear();
                                      _loadStockData();
                                    },
                                  )
                                : null,
                            isDense: true,
                            filled: true,
                            fillColor: AppColors.background,
                            border: OutlineInputBorder(borderRadius: BorderRadius.circular(9), borderSide: BorderSide.none),
                          ),
                          onSubmitted: (_) => _loadStockData(),
                        ),
                      ),
                      const SizedBox(width: 12),
                      DropdownButton<String?>(
                        value: _stateFilter,
                        hint: const Text('Tous les états'),
                        items: const [
                          DropdownMenuItem(value: null, child: Text('Tous les états')),
                          DropdownMenuItem(value: 'new', child: Text('Neuf')),
                          DropdownMenuItem(value: 'used', child: Text('Occasion')),
                          DropdownMenuItem(value: 'refurbished', child: Text('Reconditionné')),
                          DropdownMenuItem(value: 'damaged', child: Text('Endommagé')),
                        ],
                        onChanged: (val) {
                          setState(() => _stateFilter = val);
                          _loadStockData();
                        },
                      ),
                    ],
                  ),
                  const SizedBox(height: 20),

                  if (_isLoading)
                    const Center(
                      child: Padding(
                        padding: EdgeInsets.all(40),
                        child: CircularProgressIndicator(),
                      ),
                    )
                  else if (_errorMessage != null)
                    Center(
                      child: Padding(
                        padding: const EdgeInsets.all(30),
                        child: Column(
                          children: [
                            const Icon(Icons.error_outline, color: Colors.red, size: 40),
                            const SizedBox(height: 10),
                            Text('Erreur: $_errorMessage'),
                            const SizedBox(height: 12),
                            ElevatedButton(onPressed: _loadStockData, child: const Text('Réessayer')),
                          ],
                        ),
                      ),
                    )
                  else if (_products.isEmpty)
                    const Center(
                      child: Padding(
                        padding: EdgeInsets.all(40),
                        child: Column(
                          children: [
                            Icon(Icons.inventory_2_outlined, size: 48, color: AppColors.muted),
                            SizedBox(height: 12),
                            Text('Aucun article en stock.', style: TextStyle(color: AppColors.muted, fontWeight: FontWeight.w600)),
                          ],
                        ),
                      ),
                    )
                  else
                    SingleChildScrollView(
                      scrollDirection: Axis.horizontal,
                      child: DataTable(
                        columns: const [
                          DataColumn(label: Text('PRODUIT & FABRICANT')),
                          DataColumn(label: Text('SKU (AUTO)')),
                          DataColumn(label: Text('CATÉGORIE')),
                          DataColumn(label: Text('ÉTAT')),
                          DataColumn(label: Text('STOCK DISPONIBLE')),
                          DataColumn(label: Text('UNITÉ')),
                          DataColumn(label: Text('ACTIONS')),
                        ],
                        rows: _products.map((product) {
                          final stateColor = _getStateColor(product.state);
                          final isOutOfStock = product.totalOnHand <= 0;

                          return DataRow(
                            cells: [
                              DataCell(Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                mainAxisAlignment: MainAxisAlignment.center,
                                children: [
                                  Text(product.name, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                                  if (product.manufacturer != null && product.manufacturer!.isNotEmpty)
                                    Text(
                                      product.manufacturer!,
                                      style: const TextStyle(fontSize: 11, color: AppColors.muted, fontWeight: FontWeight.w500),
                                    ),
                                  if (product.observation != null && product.observation!.isNotEmpty) ...[
                                    const SizedBox(height: 2),
                                    Tooltip(
                                      message: product.observation!,
                                      child: Row(
                                        mainAxisSize: MainAxisSize.min,
                                        children: [
                                          Icon(Icons.notes, size: 12, color: Colors.blueGrey.shade600),
                                          const SizedBox(width: 4),
                                          ConstrainedBox(
                                            constraints: const BoxConstraints(maxWidth: 180),
                                            child: Text(
                                              product.observation!,
                                              maxLines: 1,
                                              overflow: TextOverflow.ellipsis,
                                              style: TextStyle(
                                                fontSize: 11,
                                                fontStyle: FontStyle.italic,
                                                color: Colors.blueGrey.shade600,
                                              ),
                                            ),
                                          ),
                                        ],
                                      ),
                                    ),
                                  ],
                                ],
                              )),
                              DataCell(Text(
                                product.sku,
                                style: const TextStyle(fontFamily: 'monospace', fontWeight: FontWeight.w600, fontSize: 12),
                              )),
                              DataCell(Text(product.category?.name ?? '—')),
                              DataCell(Container(
                                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                                decoration: BoxDecoration(
                                  color: stateColor.withValues(alpha: 0.15),
                                  borderRadius: BorderRadius.circular(10),
                                ),
                                child: Text(
                                  product.stateLabel,
                                  style: TextStyle(color: stateColor, fontWeight: FontWeight.bold, fontSize: 11),
                                ),
                              )),
                              DataCell(Container(
                                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                                decoration: BoxDecoration(
                                  color: isOutOfStock
                                      ? Colors.red.withValues(alpha: 0.12)
                                      : Colors.green.withValues(alpha: 0.12),
                                  borderRadius: BorderRadius.circular(8),
                                ),
                                child: Text(
                                  product.totalOnHand.toStringAsFixed(0),
                                  style: TextStyle(
                                    fontWeight: FontWeight.bold,
                                    fontSize: 13,
                                    color: isOutOfStock ? Colors.red.shade800 : Colors.green.shade800,
                                  ),
                                ),
                              )),
                              DataCell(Text(
                                product.piecesPerCarton != null && product.piecesPerCarton! > 0
                                    ? '${product.baseUnit?.code ?? "CRT"} (${product.piecesPerCarton!.toStringAsFixed(0)} pcs/ctn)'
                                    : (product.baseUnit?.code ?? '—'),
                              )),
                              DataCell(IconButton(
                                icon: const Icon(Icons.archive_outlined, size: 18, color: Colors.grey),
                                tooltip: 'Archiver',
                                onPressed: () async {
                                  final confirm = await showDialog<bool>(
                                    context: context,
                                    builder: (c) => AlertDialog(
                                      title: const Text('Archiver l\'article'),
                                      content: Text('Voulez-vous archiver "${product.name}" ?'),
                                      actions: [
                                        TextButton(onPressed: () => Navigator.pop(c, false), child: const Text('Annuler')),
                                        FilledButton(onPressed: () => Navigator.pop(c, true), child: const Text('Archiver')),
                                      ],
                                    ),
                                  );
                                  if (confirm == true) {
                                    await CatalogService.instance.archiveProduct(product.id);
                                    _loadStockData();
                                  }
                                },
                              )),
                            ],
                          );
                        }).toList(),
                      ),
                    ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}
