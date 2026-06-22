import type { DocumentType } from '@/types';

export const getFileIcon = (type: DocumentType): string => {
  const icons: Record<DocumentType, string> = {
    pdf: '📄',
    excel: '📊',
    word: '📝',
    powerpoint: '📊',
    image: '🖼️',
    video: '🎥',
    audio: '🎵',
    text: '📄',
    archive: '📦'
  };
  return icons[type];
};

export const getFileIconColor = (type: DocumentType): string => {
  const colors: Record<DocumentType, string> = {
    pdf: 'text-red-500',
    excel: 'text-green-500',
    word: 'text-blue-500',
    powerpoint: 'text-orange-500',
    image: 'text-purple-500',
    video: 'text-pink-500',
    audio: 'text-yellow-500',
    text: 'text-gray-500',
    archive: 'text-indigo-500'
  };
  return colors[type];
};

export const formatFileSize = (bytes: number): string => {
  if (bytes === 0) return '0 B';
  
  const k = 1024;
  const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

export const formatDate = (date: Date): string => {
  return new Intl.DateTimeFormat('fr-FR', {
    year: 'numeric',
    month: 'short',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit'
  }).format(date);
};

export const getFileExtension = (type: DocumentType): string => {
  const extensions: Record<DocumentType, string> = {
    pdf: 'PDF',
    excel: 'XLSX',
    word: 'DOCX',
    powerpoint: 'PPTX',
    image: 'IMG',
    video: 'MP4',
    audio: 'MP3',
    text: 'TXT',
    archive: 'ZIP'
  };
  return extensions[type];
};