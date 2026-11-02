import { describe, it, expect, vi, beforeEach } from 'vitest';
import messageService from '../requests/messageService';
import api from '@helpers/axios';

vi.mock('@helpers/axios', () => {
  return {
    default: {
      get: vi.fn(),
      post: vi.fn(),
      patch: vi.fn(),
      delete: vi.fn(),
    },
  };
});

describe('messageService', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('getInbox calls /api/messages with query params', async () => {
    const params = { status: 'unread', search: 'urgent' };
    api.get.mockResolvedValueOnce({ data: { messages: [], unreadCount: 0 } });

    await messageService.getInbox(params);

    expect(api.get).toHaveBeenCalledWith('/api/messages', { params });
  });

  it('getUnreadCount calls /api/messages/unread-count', async () => {
    api.get.mockResolvedValueOnce({ data: { unreadCount: 5 } });

    const res = await messageService.getUnreadCount();

    expect(api.get).toHaveBeenCalledWith('/api/messages/unread-count');
    expect(res.data.unreadCount).toBe(5);
  });

  it('getMessage calls /api/messages/:id', async () => {
    api.get.mockResolvedValueOnce({ data: { id: 42, sujet: 'Test' } });

    const res = await messageService.getMessage(42);

    expect(api.get).toHaveBeenCalledWith('/api/messages/42');
    expect(res.data.sujet).toBe('Test');
  });

  it('toggleRead sends PATCH to /api/messages/:id/read', async () => {
    api.patch.mockResolvedValueOnce({ data: { success: true, isLu: true } });

    await messageService.toggleRead(42, true);

    expect(api.patch).toHaveBeenCalledWith('/api/messages/42/read', { isLu: true });
  });

  it('markAllAsRead sends PATCH to /api/messages/read-all', async () => {
    api.patch.mockResolvedValueOnce({ data: { success: true } });

    await messageService.markAllAsRead();

    expect(api.patch).toHaveBeenCalledWith('/api/messages/read-all');
  });

  it('downloadPieceJointeUrl formats the download url properly', () => {
    const url = messageService.downloadPieceJointeUrl(10, 5);
    expect(url).toBe('/api/messages/10/pieces-jointes/5');
  });

  it('toggleArchive sends PATCH to /api/messages/:id/archive', async () => {
    api.patch.mockResolvedValueOnce({ data: { success: true, isArchive: true } });

    await messageService.toggleArchive(15, true);

    expect(api.patch).toHaveBeenCalledWith('/api/messages/15/archive', { isArchive: true });
  });

  it('getSentMessages calls /api/messages/sent', async () => {
    api.get.mockResolvedValueOnce({ data: { messages: [] } });

    await messageService.getSentMessages();

    expect(api.get).toHaveBeenCalledWith('/api/messages/sent');
  });

  it('getSentMessageStats calls /api/messages/sent/:id/stats', async () => {
    api.get.mockResolvedValueOnce({ data: { total: 10, lus: 8 } });

    await messageService.getSentMessageStats(99);

    expect(api.get).toHaveBeenCalledWith('/api/messages/sent/99/stats');
  });

  it('getTargets calls /api/messages/targets', async () => {
    api.get.mockResolvedValueOnce({ data: { semestres: [] } });

    await messageService.getTargets();

    expect(api.get).toHaveBeenCalledWith('/api/messages/targets');
  });

  it('sendMessage with JSON object calls api.post without multipart header', async () => {
    const payload = { sujet: 'Hello', message: 'World' };
    api.post.mockResolvedValueOnce({ data: { success: true } });

    await messageService.sendMessage(payload);

    expect(api.post).toHaveBeenCalledWith('/api/messages', payload);
  });

  it('sendMessage with FormData calls api.post with multipart header', async () => {
    const formData = new FormData();
    formData.append('sujet', 'Avec PJ');
    api.post.mockResolvedValueOnce({ data: { success: true } });

    await messageService.sendMessage(formData);

    expect(api.post).toHaveBeenCalledWith('/api/messages', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
  });
});

