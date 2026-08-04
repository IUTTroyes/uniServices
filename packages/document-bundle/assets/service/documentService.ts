import api from '@helpers/axios';
import type { Category, Document } from '@types';

const defaultHeaders = {
  'Accept': 'application/ld+json, application/json'
};

export const documentService = {
  async fetchCategories(options?: { activePackages?: string[]; currentDepartmentId?: string }): Promise<Category[]> {
    try {
      const response = await api.get('/api/document_categories', { headers: defaultHeaders });
      const data = response.data;
      const rawItems = Array.isArray(data)
        ? data
        : (data['hydra:member'] || data['member'] || data['data'] || []);

      if (!Array.isArray(rawItems)) return [];

      const activePackages = options?.activePackages || [];
      const currentDeptId = options?.currentDepartmentId ? options.currentDepartmentId.toString() : undefined;

      const categoryMap = new Map<string, Category>();

      // Step 1: Create Category objects for matching items
      for (const item of rawItems) {
        const id = item.id
          ? item.id.toString()
          : item['@id']
            ? item['@id'].split('/').pop()
            : '';

        if (!id) continue;

        const packageKey = item.packageKey || undefined;
        const isSystem = !!item.isSystem;
        let departementId: string | undefined = undefined;
        if (item.departement) {
          if (typeof item.departement === 'object' && item.departement.id) {
            departementId = item.departement.id.toString();
          } else if (typeof item.departement === 'string') {
            departementId = item.departement.split('/').pop();
          }
        }

        // Package filtering: If category has packageKey, package must be active
        if (packageKey && activePackages.length > 0 && !activePackages.includes(packageKey) && !activePackages.includes('core')) {
          continue;
        }

        // Department filtering: If category has departementId, it must match current department
        if (departementId && currentDeptId && departementId !== currentDeptId) {
          continue;
        }

        let parentId: string | undefined = undefined;
        if (item.parent) {
          if (typeof item.parent === 'object' && item.parent.id) {
            parentId = item.parent.id.toString();
          } else if (typeof item.parent === 'string') {
            parentId = item.parent.split('/').pop();
          }
        }

        categoryMap.set(id, {
          id,
          name: item.libelle || item.name || 'Sans nom',
          parentId,
          icon: item.icon || '📁',
          color: item.color || 'bg-blue-500',
          packageKey,
          isSystem,
          departementId,
          documentCount: item.documentCount || 0,
          children: []
        });
      }

      // Step 2: Build tree (attach children to parents) & collect root categories
      const rootCategories: Category[] = [];
      for (const category of categoryMap.values()) {
        if (category.parentId && categoryMap.has(category.parentId)) {
          const parent = categoryMap.get(category.parentId)!;
          parent.children!.push(category);
        } else {
          rootCategories.push(category);
        }
      }

      return rootCategories;
    } catch (e) {
      console.error('API Error fetching document categories:', e);
      return [];
    }
  },

  async fetchDocuments(params?: { categoryId?: string; isFavorite?: boolean; query?: string }): Promise<Document[]> {
    try {
      const response = await api.get('/api/documents', { headers: defaultHeaders });
      const data = response.data;
      const rawItems = Array.isArray(data)
        ? data
        : (data['hydra:member'] || data['member'] || data['data'] || []);

      if (!Array.isArray(rawItems)) return [];

      let docs: Document[] = rawItems.map((item: any) => {
        const id = item.id
          ? item.id.toString()
          : item['@id']
            ? item['@id'].split('/').pop()
            : Date.now().toString();

        let categoryId = '';
        if (item.category) {
          if (typeof item.category === 'object' && item.category.id) {
            categoryId = item.category.id.toString();
          } else if (typeof item.category === 'string') {
            categoryId = item.category.split('/').pop() || '';
          }
        }

        let departementId: string | undefined = undefined;
        if (item.departement) {
          if (typeof item.departement === 'object' && item.departement.id) {
            departementId = item.departement.id.toString();
          } else if (typeof item.departement === 'string') {
            departementId = item.departement.split('/').pop();
          }
        }

        return {
          id,
          title: item.titre || item.title || 'Document sans titre',
          type: item.type || 'pdf',
          size: item.fileSize || item.size || 0,
          lastModified: new Date(item.updatedAt || item.createdAt || Date.now()),
          description: item.description,
          categoryId,
          departementId,
          isFavorite: !!item.isFavorite,
          author: item.author || 'Inconnu',
          version: item.version || 'v1.0',
          tags: Array.isArray(item.tags) ? item.tags : []
        };
      });

      if (params?.categoryId) {
        docs = docs.filter(d => d.categoryId === params.categoryId);
      }
      if (params?.isFavorite) {
        docs = docs.filter(d => d.isFavorite);
      }
      if (params?.query) {
        const q = params.query.toLowerCase();
        docs = docs.filter(d =>
          d.title.toLowerCase().includes(q) ||
          d.description?.toLowerCase().includes(q) ||
          d.author.toLowerCase().includes(q) ||
          d.tags.some(t => t.toLowerCase().includes(q))
        );
      }
      return docs;
    } catch (e) {
      console.error('API Error fetching documents:', e);
      return [];
    }
  },

  async toggleFavorite(documentId: string, currentStatus: boolean): Promise<boolean> {
    const response = await api.patch(`/api/documents/${documentId}`, {
      isFavorite: !currentStatus
    }, {
      headers: { ...defaultHeaders, 'Content-Type': 'application/merge-patch+json' }
    });
    return !!response.data.isFavorite;
  },

  async createDocument(newDoc: {
    titre: string;
    description?: string;
    type: string;
    categoryId?: string;
    departementId?: string;
    tags?: string[];
    author?: string;
  }): Promise<Document> {
    const response = await api.post('/api/documents', {
      titre: newDoc.titre,
      description: newDoc.description || '',
      filename: newDoc.titre.toLowerCase().replace(/[^a-z0-9]/g, '_') + '.' + (newDoc.type === 'excel' ? 'xlsx' : 'pdf'),
      mimeType: 'application/pdf',
      fileSize: Math.floor(Math.random() * 5000000) + 1024,
      type: newDoc.type,
      author: newDoc.author || 'Utilisateur Connecté',
      version: 'v1.0',
      tags: newDoc.tags || [],
      isFavorite: false,
      visibility: 'PUBLIC',
      category: newDoc.categoryId ? `/api/document_categories/${newDoc.categoryId}` : null,
      departement: newDoc.departementId ? `/api/structure_departements/${newDoc.departementId}` : null
    }, {
      headers: { ...defaultHeaders, 'Content-Type': 'application/ld+json' }
    });

    const item = response.data;
    let categoryId = newDoc.categoryId || '';
    if (item.category) {
      if (typeof item.category === 'object' && item.category.id) {
        categoryId = item.category.id.toString();
      } else if (typeof item.category === 'string') {
        categoryId = item.category.split('/').pop() || '';
      }
    }

    return {
      id: item.id ? item.id.toString() : item['@id'].split('/').pop(),
      title: item.titre,
      type: item.type,
      size: item.fileSize,
      lastModified: new Date(item.createdAt || Date.now()),
      description: item.description,
      categoryId,
      departementId: newDoc.departementId,
      isFavorite: item.isFavorite,
      author: item.author,
      version: item.version,
      tags: item.tags || []
    };
  },

  async deleteDocument(documentId: string): Promise<void> {
    await api.delete(`/api/documents/${documentId}`, { headers: defaultHeaders });
  },

  updateCategoryCounts(categories: Category[], documents: Document[]): void {
    const computeCount = (cat: Category): number => {
      const categoryIds = new Set<string>([cat.id]);
      const collectSubIds = (subs?: Category[]) => {
        if (!subs) return;
        for (const sub of subs) {
          categoryIds.add(sub.id);
          collectSubIds(sub.children);
        }
      };
      collectSubIds(cat.children);

      const total = documents.filter(d => categoryIds.has(d.categoryId)).length;
      cat.documentCount = total;

      if (cat.children) {
        for (const child of cat.children) {
          computeCount(child);
        }
      }
      return total;
    };

    for (const category of categories) {
      computeCount(category);
    }
  }
};
