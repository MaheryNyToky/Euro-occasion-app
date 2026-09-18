import 'package:flutter/material.dart';

import '../../../core/theme/app_theme.dart';
import '../models/catalog_models.dart';
import '../services/catalog_service.dart';
import '../services/partner_service.dart';

class CatalogPage extends StatefulWidget {
  const CatalogPage({super.key});

  @override
  State<CatalogPage> createState() => _CatalogPageState();
}

class _CatalogPageState extends State<CatalogPage> {
  int _tab = 0;
  final _tabs = const ['Produits', 'Catégories', 'Unités', 'Fournisseurs', 'Clients'];

  List<ProductModel> _products = [];
  List<CategoryModel> _categories = [];
  List<UnitModel> _units = [];
  List<SupplierModel> _suppliers = [];
  List<CustomerModel> _customers = [];

  bool _isLoading = false;
  String? _errorMessage;
  final _searchController = TextEditingController();
  String? _stateFilter;

  @override
  void initState() {
    super.initState();
    _loadDataForCurrentTab();
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  Future<void> _loadDataForCurrentTab() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      switch (_tab) {
        case 0:
          final res = await Future.wait([
            CatalogService.instance.getProducts(
              search: _searchController.text.trim(),
              state: _stateFilter,
            ),
            CatalogService.instance.getCategories(),
            CatalogService.instance.getUnits(),
          ]);
          _products = res[0] as List<ProductModel>;
          _categories = res[1] as List<CategoryModel>;
          _units = res[2] as List<UnitModel>;
          break;
        case 1:
          _categories = await CatalogService.instance.getCategories();
          break;
        case 2:
          _units = await CatalogService.instance.getUnits();
          break;
        case 3:
          _suppliers = await PartnerService.instance.getSuppliers();
          break;
        case 4:
          _customers = await PartnerService.instance.getCustomers();
          break;
      }
    } catch (e) {
      _errorMessage = e.toString();
    } finally {
      if (mounted) {
        setState(() => _isLoading = false);
      }
    }
  }

  void _onTabChanged(int index) {
    setState(() {
      _tab = index;
      _searchController.clear();
      _stateFilter = null;
    });
    _loadDataForCurrentTab();
  }

  void _showAddProductDialog() {
    final nameCtrl = TextEditingController();
    final skuCtrl = TextEditingController();
    final refCtrl = TextEditingController();
    final alertCtrl = TextEditingController(text: '0');
    String selectedState = 'new';
    String? selectedCategoryId = _categories.isNotEmpty ? _categories.first.id : null;
    String? selectedUnitId = _units.isNotEmpty ? _units.first.id : null;

    if (_units.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Veuillez d\'abord créer au moins une unité de mesure.')),
      );
      return;
    }

    showDialog(
      context: context,
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, setDialogState) => AlertDialog(
          title: const Text('Nouveau Produit'),
          content: SizedBox(
            width: 460,
            child: SingleChildScrollView(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  TextField(controller: nameCtrl, decoration: const InputDecoration(labelText: 'Nom du produit *')),
                  const SizedBox(height: 12),
                  TextField(controller: skuCtrl, decoration: const InputDecoration(labelText: 'Code SKU *', hintText: 'ex: IPHONE-14-128')),
                  const SizedBox(height: 12),
                  TextField(controller: refCtrl, decoration: const InputDecoration(labelText: 'Référence fabricant')),
                  const SizedBox(height: 12),
                  DropdownButtonFormField<String>(
                    initialValue: selectedState,
                    decoration: const InputDecoration(labelText: 'État du produit'),
                    items: const [
                      DropdownMenuItem(value: 'new', child: Text('Neuf')),
                      DropdownMenuItem(value: 'used', child: Text('Occasion')),
                      DropdownMenuItem(value: 'refurbished', child: Text('Reconditionné')),
                      DropdownMenuItem(value: 'damaged', child: Text('Endommagé')),
                    ],
                    onChanged: (v) => setDialogState(() => selectedState = v ?? 'new'),
                  ),
                  const SizedBox(height: 12),
                  if (_categories.isNotEmpty)
                    DropdownButtonFormField<String?>(
                      initialValue: selectedCategoryId,
                      decoration: const InputDecoration(labelText: 'Catégorie'),
                      items: [
                        const DropdownMenuItem(value: null, child: Text('Aucune')),
                        ..._categories.map((c) => DropdownMenuItem(value: c.id, child: Text(c.name))),
                      ],
                      onChanged: (v) => setDialogState(() => selectedCategoryId = v),
                    ),
                  const SizedBox(height: 12),
                  DropdownButtonFormField<String>(
                    initialValue: selectedUnitId,
                    decoration: const InputDecoration(labelText: 'Unité de base *'),
                    items: _units.map((u) => DropdownMenuItem(value: u.id, child: Text('${u.name} (${u.code})'))).toList(),
                    onChanged: (v) => setDialogState(() => selectedUnitId = v),
                  ),
                  const SizedBox(height: 12),
                  TextField(
                    controller: alertCtrl,
                    keyboardType: TextInputType.number,
                    decoration: const InputDecoration(labelText: 'Seuil d\'alerte stock'),
                  ),
                ],
              ),
            ),
          ),
          actions: [
            TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('Annuler')),
            FilledButton(
              onPressed: () async {
                if (nameCtrl.text.trim().isEmpty || skuCtrl.text.trim().isEmpty || selectedUnitId == null) {
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(content: Text('Veuillez remplir les champs obligatoires (*)')),
                  );
                  return;
                }

                Navigator.pop(ctx);
                try {
                  await CatalogService.instance.createProduct({
                    'name': nameCtrl.text.trim(),
                    'sku': skuCtrl.text.trim(),
                    'reference': refCtrl.text.trim().isEmpty ? null : refCtrl.text.trim(),
                    'state': selectedState,
                    'category_id': selectedCategoryId,
                    'base_unit_id': selectedUnitId,
                    'alert_threshold': double.tryParse(alertCtrl.text.trim()) ?? 0,
                  });
                  if (!mounted) return;
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(content: Text('Produit créé avec succès dans l\'ERP.')),
                  );
                  _loadDataForCurrentTab();
                } catch (e) {
                  if (!mounted) return;
                  ScaffoldMessenger.of(context).showSnackBar(
                    SnackBar(content: Text('Erreur: $e'), backgroundColor: Colors.red),
                  );
                }
              },
              child: const Text('Enregistrer'),
            ),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(30),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('Catalogue', style: TextStyle(fontSize: 28, fontWeight: FontWeight.w700, color: AppColors.text)),
                    SizedBox(height: 6),
                    Text('Gérez vos produits et vos référentiels métier connectés en temps réel.', style: TextStyle(color: AppColors.muted)),
                  ],
                ),
              ),
              IconButton(
                tooltip: 'Actualiser',
                icon: const Icon(Icons.refresh),
                onPressed: _isLoading ? null : _loadDataForCurrentTab,
              ),
              const SizedBox(width: 8),
              if (_tab == 0)
                FilledButton.icon(
                  onPressed: _showAddProductDialog,
                  icon: const Icon(Icons.add),
                  label: const Text('Nouveau produit'),
                ),
            ],
          ),
          const SizedBox(height: 26),
          Card(
            child: Column(
              children: [
                SingleChildScrollView(
                  scrollDirection: Axis.horizontal,
                  child: Row(
                    children: List.generate(
                      _tabs.length,
                      (index) => _CatalogTab(
                        label: _tabs[index],
                        selected: _tab == index,
                        onTap: () => _onTabChanged(index),
                      ),
                    ),
                  ),
                ),
                const Divider(height: 1),
                Padding(
                  padding: const EdgeInsets.all(22),
                  child: _buildTabContent(),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildTabContent() {
    if (_isLoading) {
      return const Center(
        child: Padding(
          padding: EdgeInsets.all(40),
          child: Column(
            children: [
              CircularProgressIndicator(),
              SizedBox(height: 16),
              Text('Chargement des données en direct...', style: TextStyle(color: AppColors.muted)),
            ],
          ),
        ),
      );
    }

    if (_errorMessage != null) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(30),
          child: Column(
            children: [
              const Icon(Icons.error_outline, color: Colors.red, size: 48),
              const SizedBox(height: 12),
              Text('Erreur de connexion API: $_errorMessage', textAlign: TextAlign.center),
              const SizedBox(height: 16),
              ElevatedButton.icon(
                onPressed: _loadDataForCurrentTab,
                icon: const Icon(Icons.refresh),
                label: const Text('Réessayer'),
              ),
            ],
          ),
        ),
      );
    }

    return switch (_tab) {
      0 => _buildProductsView(),
      1 => _buildCategoriesView(),
      2 => _buildUnitsView(),
      3 => _buildSuppliersView(),
      _ => _buildCustomersView(),
    };
  }

  Widget _buildProductsView() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Expanded(
              child: TextField(
                controller: _searchController,
                decoration: InputDecoration(
                  hintText: 'Rechercher par nom, SKU ou référence',
                  prefixIcon: const Icon(Icons.search),
                  suffixIcon: _searchController.text.isNotEmpty
                      ? IconButton(
                          icon: const Icon(Icons.clear, size: 18),
                          onPressed: () {
                            _searchController.clear();
                            _loadDataForCurrentTab();
                          },
                        )
                      : null,
                  isDense: true,
                  filled: true,
                  fillColor: AppColors.background,
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(9), borderSide: BorderSide.none),
                ),
                onSubmitted: (_) => _loadDataForCurrentTab(),
              ),
            ),
            const SizedBox(width: 12),
            DropdownButton<String?>(
              value: _stateFilter,
              hint: const Text('État'),
              items: const [
                DropdownMenuItem(value: null, child: Text('Tous les états')),
                DropdownMenuItem(value: 'new', child: Text('Neuf')),
                DropdownMenuItem(value: 'used', child: Text('Occasion')),
                DropdownMenuItem(value: 'refurbished', child: Text('Reconditionné')),
                DropdownMenuItem(value: 'damaged', child: Text('Endommagé')),
              ],
              onChanged: (val) {
                setState(() => _stateFilter = val);
                _loadDataForCurrentTab();
              },
            ),
          ],
        ),
        const SizedBox(height: 20),
        if (_products.isEmpty)
          const Center(
            child: Padding(
              padding: EdgeInsets.all(40),
              child: Column(
                children: [
                  Icon(Icons.inventory_2_outlined, size: 48, color: AppColors.muted),
                  SizedBox(height: 12),
                  Text('Aucun produit trouvé dans ce tenant.', style: TextStyle(color: AppColors.muted, fontWeight: FontWeight.w600)),
                ],
              ),
            ),
          )
        else
          SingleChildScrollView(
            scrollDirection: Axis.horizontal,
            child: DataTable(
              columns: const [
                DataColumn(label: Text('PRODUIT')),
                DataColumn(label: Text('SKU')),
                DataColumn(label: Text('CATÉGORIE')),
                DataColumn(label: Text('UNITÉ')),
                DataColumn(label: Text('ÉTAT')),
                DataColumn(label: Text('VARIANTES')),
                DataColumn(label: Text('ACTIONS')),
              ],
              rows: _products.map((product) {
                return DataRow(
                  cells: [
                    DataCell(Text(product.name, style: const TextStyle(fontWeight: FontWeight.bold))),
                    DataCell(Text(product.sku)),
                    DataCell(Text(product.category?.name ?? '—')),
                    DataCell(Text(product.baseUnit?.code ?? '—')),
                    DataCell(Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                      decoration: BoxDecoration(
                        color: _getStateColor(product.state).withValues(alpha: 0.15),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Text(
                        product.stateLabel,
                        style: TextStyle(color: _getStateColor(product.state), fontWeight: FontWeight.bold, fontSize: 11),
                      ),
                    )),
                    DataCell(Text(product.variants.isNotEmpty ? '${product.variants.length}' : '—')),
                    DataCell(IconButton(
                      icon: const Icon(Icons.archive_outlined, size: 18, color: Colors.grey),
                      tooltip: 'Archiver',
                      onPressed: () async {
                        final confirm = await showDialog<bool>(
                          context: context,
                          builder: (c) => AlertDialog(
                            title: const Text('Archiver le produit'),
                            content: Text('Voulez-vous archiver "${product.name}" ?'),
                            actions: [
                              TextButton(onPressed: () => Navigator.pop(c, false), child: const Text('Annuler')),
                              FilledButton(onPressed: () => Navigator.pop(c, true), child: const Text('Archiver')),
                            ],
                          ),
                        );
                        if (confirm == true) {
                          await CatalogService.instance.archiveProduct(product.id);
                          _loadDataForCurrentTab();
                        }
                      },
                    )),
                  ],
                );
              }).toList(),
            ),
          ),
      ],
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

  Widget _buildCategoriesView() {
    if (_categories.isEmpty) {
      return const Center(child: Padding(padding: EdgeInsets.all(30), child: Text('Aucune catégorie enregistrée.')));
    }
    return SingleChildScrollView(
      scrollDirection: Axis.horizontal,
      child: DataTable(
        columns: const [DataColumn(label: Text('CODE')), DataColumn(label: Text('NOM')), DataColumn(label: Text('SOUS-CATÉGORIES'))],
        rows: _categories.map((c) => DataRow(cells: [
          DataCell(Text(c.code, style: const TextStyle(fontWeight: FontWeight.bold))),
          DataCell(Text(c.name)),
          DataCell(Text('${c.children.length}')),
        ])).toList(),
      ),
    );
  }

  Widget _buildUnitsView() {
    if (_units.isEmpty) {
      return const Center(child: Padding(padding: EdgeInsets.all(30), child: Text('Aucune unité enregistrée.')));
    }
    return SingleChildScrollView(
      scrollDirection: Axis.horizontal,
      child: DataTable(
        columns: const [DataColumn(label: Text('CODE')), DataColumn(label: Text('NOM')), DataColumn(label: Text('PRÉCISION')), DataColumn(label: Text('TYPE'))],
        rows: _units.map((u) => DataRow(cells: [
          DataCell(Text(u.code, style: const TextStyle(fontWeight: FontWeight.bold))),
          DataCell(Text(u.name)),
          DataCell(Text('${u.precision} décimale(s)')),
          DataCell(Text(u.isBase ? 'Base' : 'Secondaire')),
        ])).toList(),
      ),
    );
  }

  Widget _buildSuppliersView() {
    if (_suppliers.isEmpty) {
      return const Center(child: Padding(padding: EdgeInsets.all(30), child: Text('Aucun fournisseur enregistré.')));
    }
    return SingleChildScrollView(
      scrollDirection: Axis.horizontal,
      child: DataTable(
        columns: const [DataColumn(label: Text('CODE')), DataColumn(label: Text('ENTREPRISE')), DataColumn(label: Text('CONTACT')), DataColumn(label: Text('DEVISE'))],
        rows: _suppliers.map((s) => DataRow(cells: [
          DataCell(Text(s.code, style: const TextStyle(fontWeight: FontWeight.bold))),
          DataCell(Text(s.companyName)),
          DataCell(Text(s.contactName ?? s.email ?? '—')),
          DataCell(Text(s.currency)),
        ])).toList(),
      ),
    );
  }

  Widget _buildCustomersView() {
    if (_customers.isEmpty) {
      return const Center(child: Padding(padding: EdgeInsets.all(30), child: Text('Aucun client enregistré.')));
    }
    return SingleChildScrollView(
      scrollDirection: Axis.horizontal,
      child: DataTable(
        columns: const [DataColumn(label: Text('CODE')), DataColumn(label: Text('CLIENT')), DataColumn(label: Text('DEVISE')), DataColumn(label: Text('LIMITE CRÉDIT'))],
        rows: _customers.map((c) => DataRow(cells: [
          DataCell(Text(c.code, style: const TextStyle(fontWeight: FontWeight.bold))),
          DataCell(Text(c.displayName)),
          DataCell(Text(c.currency)),
          DataCell(Text('${c.creditLimit.toStringAsFixed(0)} ${c.currency}')),
        ])).toList(),
      ),
    );
  }
}

class _CatalogTab extends StatelessWidget {
  const _CatalogTab({required this.label, required this.selected, required this.onTap});

  final String label;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) => InkWell(
        onTap: onTap,
        child: Container(
          padding: const EdgeInsets.fromLTRB(20, 17, 20, 14),
          decoration: BoxDecoration(
            border: Border(bottom: BorderSide(color: selected ? AppColors.primary : Colors.transparent, width: 2)),
          ),
          child: Text(
            label,
            style: TextStyle(
              color: selected ? AppColors.primary : AppColors.muted,
              fontWeight: selected ? FontWeight.w700 : FontWeight.w500,
            ),
          ),
        ),
      );
}
