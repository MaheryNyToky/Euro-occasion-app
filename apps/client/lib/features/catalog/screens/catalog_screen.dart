import 'package:flutter/material.dart';
import '../../auth/services/auth_service.dart';
import '../models/catalog_models.dart';
import '../services/catalog_service.dart';

class CatalogScreen extends StatefulWidget {
  final VoidCallback onLogout;

  const CatalogScreen({super.key, required this.onLogout});

  @override
  State<CatalogScreen> createState() => _CatalogScreenState();
}

class _CatalogScreenState extends State<CatalogScreen> {
  final _searchController = TextEditingController();
  List<ProductModel> _products = [];
  List<CategoryModel> _categories = [];

  bool _isLoading = false;
  String? _errorMessage;
  String? _selectedCategory;
  String? _selectedState;

  @override
  void initState() {
    super.initState();
    _loadInitialData();
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  Future<void> _loadInitialData() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      final results = await Future.wait([
        CatalogService.instance.getProducts(
          search: _searchController.text.trim(),
          categoryId: _selectedCategory,
          state: _selectedState,
        ),
        CatalogService.instance.getCategories(),
      ]);

      setState(() {
        _products = results[0] as List<ProductModel>;
        _categories = results[1] as List<CategoryModel>;
      });
    } catch (e) {
      setState(() {
        _errorMessage = e.toString();
      });
    } finally {
      if (mounted) {
        setState(() {
          _isLoading = false;
        });
      }
    }
  }

  Future<void> _refreshProducts() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      final products = await CatalogService.instance.getProducts(
        search: _searchController.text.trim(),
        categoryId: _selectedCategory,
        state: _selectedState,
      );

      setState(() {
        _products = products;
      });
    } catch (e) {
      setState(() {
        _errorMessage = e.toString();
      });
    } finally {
      if (mounted) {
        setState(() {
          _isLoading = false;
        });
      }
    }
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

  void _showProductDetails(ProductModel product) {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Row(
          children: [
            Expanded(child: Text(product.name, style: const TextStyle(fontWeight: FontWeight.bold))),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
              decoration: BoxDecoration(
                color: _getStateColor(product.state).withValues(alpha: 0.15),
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: _getStateColor(product.state)),
              ),
              child: Text(
                product.stateLabel,
                style: TextStyle(color: _getStateColor(product.state), fontWeight: FontWeight.bold, fontSize: 12),
              ),
            ),
          ],
        ),
        content: SizedBox(
          width: 500,
          child: SingleChildScrollView(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisSize: MainAxisSize.min,
              children: [
                _buildDetailRow('SKU', product.sku),
                if (product.reference != null) _buildDetailRow('Référence', product.reference!),
                if (product.category != null) _buildDetailRow('Catégorie', product.category!.name),
                if (product.baseUnit != null) _buildDetailRow('Unité de base', '${product.baseUnit!.name} (${product.baseUnit!.code})'),
                _buildDetailRow('Seuil d\'alerte', product.alertThreshold.toString()),
                _buildDetailRow('N° de série requis', product.requiresSerialNumber ? 'Oui' : 'Non'),
                if (product.description != null && product.description!.isNotEmpty) ...[
                  const SizedBox(height: 12),
                  const Text('Description :', style: TextStyle(fontWeight: FontWeight.bold)),
                  Text(product.description!),
                ],
                if (product.variants.isNotEmpty) ...[
                  const Divider(height: 24),
                  Text('Variantes (${product.variants.length}) :', style: const TextStyle(fontWeight: FontWeight.bold)),
                  const SizedBox(height: 8),
                  ...product.variants.map((v) => Card(
                        margin: const EdgeInsets.only(bottom: 6),
                        child: ListTile(
                          dense: true,
                          title: Text(v.name ?? v.sku, style: const TextStyle(fontWeight: FontWeight.w600)),
                          subtitle: Text('SKU: ${v.sku}${v.qrCode != null ? ' | QR: ${v.qrCode}' : ''}'),
                          trailing: v.isActive
                              ? const Chip(label: Text('Actif', style: TextStyle(fontSize: 10)))
                              : const Chip(label: Text('Inactif', style: TextStyle(fontSize: 10))),
                        ),
                      )),
                ],
              ],
            ),
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(ctx).pop(),
            child: const Text('Fermer'),
          ),
          FilledButton.icon(
            style: FilledButton.styleFrom(backgroundColor: Colors.red.shade700),
            onPressed: () async {
              Navigator.of(ctx).pop();
              await _archiveProduct(product);
            },
            icon: const Icon(Icons.archive_outlined, size: 18),
            label: const Text('Archiver'),
          ),
        ],
      ),
    );
  }

  Future<void> _archiveProduct(ProductModel product) async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Confirmer l\'archivage'),
        content: Text('Voulez-vous archiver le produit "${product.name}" ? Il ne sera plus visible dans le catalogue actif.'),
        actions: [
          TextButton(onPressed: () => Navigator.of(ctx).pop(false), child: const Text('Annuler')),
          FilledButton(
            style: FilledButton.styleFrom(backgroundColor: Colors.red),
            onPressed: () => Navigator.of(ctx).pop(true),
            child: const Text('Confirmer'),
          ),
        ],
      ),
    );

    if (confirm == true) {
      try {
        await CatalogService.instance.archiveProduct(product.id);
        if (!mounted) return;
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Produit archivé avec succès.')),
        );
        _refreshProducts();
      } catch (e) {
        if (!mounted) return;
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Erreur : $e'), backgroundColor: Colors.red),
        );
      }
    }
  }

  Widget _buildDetailRow(String label, String value) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(width: 140, child: Text('$label :', style: const TextStyle(fontWeight: FontWeight.w600, color: Colors.black54))),
          Expanded(child: Text(value, style: const TextStyle(fontWeight: FontWeight.w500))),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final session = AuthService.instance.currentSession;
    final theme = Theme.of(context);

    return Scaffold(
      appBar: AppBar(
        title: Row(
          children: [
            const Icon(Icons.inventory_2),
            const SizedBox(width: 10),
            Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text('Catalogue & Référentiels', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                if (session != null)
                  Text(
                    '${session.tenant.name} (${session.tenant.code}) • ${session.user.name}',
                    style: TextStyle(fontSize: 12, color: theme.colorScheme.onSurfaceVariant),
                  ),
              ],
            ),
          ],
        ),
        actions: [
          IconButton(
            tooltip: 'Actualiser',
            icon: const Icon(Icons.refresh),
            onPressed: _isLoading ? null : _refreshProducts,
          ),
          IconButton(
            tooltip: 'Se déconnecter',
            icon: const Icon(Icons.logout),
            onPressed: () async {
              await AuthService.instance.logout();
              widget.onLogout();
            },
          ),
        ],
      ),
      body: Column(
        children: [
          // Filter Bar
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: theme.colorScheme.surfaceContainerHighest.withValues(alpha: 0.3),
              border: Border(bottom: BorderSide(color: theme.dividerColor)),
            ),
            child: Wrap(
              spacing: 12,
              runSpacing: 12,
              crossAxisAlignment: WrapCrossAlignment.center,
              children: [
                // Search Input
                SizedBox(
                  width: 280,
                  child: TextField(
                    controller: _searchController,
                    decoration: InputDecoration(
                      hintText: 'Rechercher nom, SKU, référence...',
                      prefixIcon: const Icon(Icons.search, size: 20),
                      suffixIcon: _searchController.text.isNotEmpty
                          ? IconButton(
                              icon: const Icon(Icons.clear, size: 18),
                              onPressed: () {
                                _searchController.clear();
                                _refreshProducts();
                              },
                            )
                          : null,
                      isDense: true,
                      border: const OutlineInputBorder(),
                    ),
                    onSubmitted: (_) => _refreshProducts(),
                  ),
                ),

                // State Filter
                DropdownButton<String?>(
                  value: _selectedState,
                  hint: const Text('Tous les états'),
                  items: const [
                    DropdownMenuItem(value: null, child: Text('Tous les états')),
                    DropdownMenuItem(value: 'new', child: Text('Neuf')),
                    DropdownMenuItem(value: 'used', child: Text('Occasion')),
                    DropdownMenuItem(value: 'refurbished', child: Text('Reconditionné')),
                    DropdownMenuItem(value: 'damaged', child: Text('Endommagé')),
                  ],
                  onChanged: (val) {
                    setState(() => _selectedState = val);
                    _refreshProducts();
                  },
                ),

                // Category Filter
                if (_categories.isNotEmpty)
                  DropdownButton<String?>(
                    value: _selectedCategory,
                    hint: const Text('Toutes catégories'),
                    items: [
                      const DropdownMenuItem(value: null, child: Text('Toutes catégories')),
                      ..._categories.map((c) => DropdownMenuItem(value: c.id, child: Text(c.name))),
                    ],
                    onChanged: (val) {
                      setState(() => _selectedCategory = val);
                      _refreshProducts();
                    },
                  ),

                ElevatedButton.icon(
                  onPressed: _refreshProducts,
                  icon: const Icon(Icons.filter_alt, size: 18),
                  label: const Text('Filtrer'),
                ),
              ],
            ),
          ),

          // Body Content (Loading / Error / Empty / Success)
          Expanded(
            child: Builder(
              builder: (context) {
                // 1. Loading State
                if (_isLoading) {
                  return const Center(
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        CircularProgressIndicator(),
                        SizedBox(height: 16),
                        Text('Chargement des produits depuis l\'API...', style: TextStyle(color: Colors.grey)),
                      ],
                    ),
                  );
                }

                // 2. Error State
                if (_errorMessage != null) {
                  return Center(
                    child: Padding(
                      padding: const EdgeInsets.all(24),
                      child: Column(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Icon(Icons.cloud_off, size: 56, color: theme.colorScheme.error),
                          const SizedBox(height: 16),
                          Text(
                            'Erreur de communication',
                            style: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.bold),
                          ),
                          const SizedBox(height: 8),
                          Text(_errorMessage!, textAlign: TextAlign.center, style: const TextStyle(color: Colors.grey)),
                          const SizedBox(height: 20),
                          FilledButton.icon(
                            onPressed: _loadInitialData,
                            icon: const Icon(Icons.refresh),
                            label: const Text('Réessayer'),
                          ),
                        ],
                      ),
                    ),
                  );
                }

                // 3. Empty State
                if (_products.isEmpty) {
                  return Center(
                    child: Padding(
                      padding: const EdgeInsets.all(24),
                      child: Column(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Icon(Icons.inventory, size: 56, color: Colors.grey.shade400),
                          const SizedBox(height: 16),
                          const Text(
                            'Aucun produit trouvé',
                            style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                          ),
                          const SizedBox(height: 8),
                          const Text(
                            'Modifiez vos filtres ou effectuez une recherche différente.',
                            style: TextStyle(color: Colors.grey),
                          ),
                          const SizedBox(height: 16),
                          OutlinedButton.icon(
                            onPressed: () {
                              _searchController.clear();
                              _selectedCategory = null;
                              _selectedState = null;
                              _refreshProducts();
                            },
                            icon: const Icon(Icons.clear_all),
                            label: const Text('Réinitialiser les filtres'),
                          ),
                        ],
                      ),
                    ),
                  );
                }

                // 4. Success State (List / Table)
                return ListView.separated(
                  padding: const EdgeInsets.all(16),
                  itemCount: _products.length,
                  separatorBuilder: (_, _) => const SizedBox(height: 8),
                  itemBuilder: (context, index) {
                    final product = _products[index];
                    final stateColor = _getStateColor(product.state);

                    return Card(
                      elevation: 1,
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                      child: ListTile(
                        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                        leading: CircleAvatar(
                          backgroundColor: stateColor.withValues(alpha: 0.15),
                          child: Icon(Icons.devices, color: stateColor),
                        ),
                        title: Row(
                          children: [
                            Expanded(
                              child: Text(
                                product.name,
                                style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15),
                              ),
                            ),
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                              decoration: BoxDecoration(
                                color: stateColor.withValues(alpha: 0.15),
                                borderRadius: BorderRadius.circular(6),
                                border: Border.all(color: stateColor.withValues(alpha: 0.5)),
                              ),
                              child: Text(
                                product.stateLabel,
                                style: TextStyle(color: stateColor, fontWeight: FontWeight.bold, fontSize: 11),
                              ),
                            ),
                          ],
                        ),
                        subtitle: Padding(
                          padding: const EdgeInsets.only(top: 6),
                          child: Wrap(
                            spacing: 12,
                            runSpacing: 4,
                            children: [
                              Text('SKU : ${product.sku}', style: const TextStyle(fontWeight: FontWeight.w600)),
                              if (product.category != null) Text('Catégorie : ${product.category!.name}'),
                              if (product.baseUnit != null) Text('Unité : ${product.baseUnit!.name}'),
                              if (product.variants.isNotEmpty)
                                Text('${product.variants.length} variante(s)', style: TextStyle(color: theme.colorScheme.primary)),
                            ],
                          ),
                        ),
                        trailing: const Icon(Icons.chevron_right),
                        onTap: () => _showProductDetails(product),
                      ),
                    );
                  },
                );
              },
            ),
          ),
        ],
      ),
    );
  }
}
