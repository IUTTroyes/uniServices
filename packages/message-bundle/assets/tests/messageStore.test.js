import { describe, it, expect, vi, beforeEach } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';
import { useMessageStore } from '../stores/messageStore';
import messageService from '../requests/messageService';

vi.mock('../requests/messageService', () => ({
  default: {
    getUnreadCount: vi.fn(),
    getInbox: vi.fn(),
    getMessage: vi.fn(),
    toggleRead: vi.fn(),
    markAllAsRead: vi.fn(),
    toggleArchive: vi.fn(),
    getSentMessages: vi.fn(),
    sendMessage: vi.fn(),
  },
}));

describe('useMessageStore', () => {
  let store;

  beforeEach(() => {
    setActivePinia(createPinia());
    store = useMessageStore();
    vi.clearAllMocks();
  });

  it('initializes with default state', () => {
    expect(store.messages).toEqual([]);
    expect(store.sentMessages).toEqual([]);
    expect(store.currentMessage).toBeNull();
    expect(store.unreadCount).toBe(0);
    expect(store.isLoading).toBe(false);
    expect(store.activeFilter).toBe('all');
    expect(store.hasUnread).toBe(false);
  });

  it('fetchUnreadCount updates unreadCount and hasUnread getter', async () => {
    messageService.getUnreadCount.mockResolvedValueOnce({
      data: { unreadCount: 3 },
    });

    const count = await store.fetchUnreadCount();

    expect(count).toBe(3);
    expect(store.unreadCount).toBe(3);
    expect(store.hasUnread).toBe(true);
  });

  it('fetchInbox sets messages and unreadCount', async () => {
    const fakeMessages = [
      { id: 1, sujet: 'Bienvenue', isLu: false, isArchive: false },
      { id: 2, sujet: 'Emploi du temps', isLu: true, isArchive: false },
    ];
    messageService.getInbox.mockResolvedValueOnce({
      data: { messages: fakeMessages, unreadCount: 1 },
    });

    await store.fetchInbox();

    expect(store.messages).toEqual(fakeMessages);
    expect(store.unreadCount).toBe(1);
    expect(store.isLoading).toBe(false);
  });

  it('fetchMessage stores currentMessage and decrements unreadCount if read', async () => {
    store.unreadCount = 2;
    const msg = { id: 1, sujet: 'Message test', isLu: true };
    messageService.getMessage.mockResolvedValueOnce({ data: msg });

    const result = await store.fetchMessage(1);

    expect(result).toEqual(msg);
    expect(store.currentMessage).toEqual(msg);
    expect(store.unreadCount).toBe(1);
  });

  it('toggleRead updates state of message in list and currentMessage', async () => {
    store.messages = [
      { id: 1, sujet: 'Msg 1', isLu: false },
      { id: 2, sujet: 'Msg 2', isLu: false },
    ];
    store.currentMessage = { id: 1, sujet: 'Msg 1', isLu: false };
    messageService.toggleRead.mockResolvedValueOnce({
      data: { success: true, isLu: true, unreadCount: 0 },
    });

    await store.toggleRead(1, true);

    expect(store.messages[0].isLu).toBe(true);
    expect(store.currentMessage.isLu).toBe(true);
    expect(store.unreadCount).toBe(0);
  });

  it('markAllAsRead sets all messages as read and resets unreadCount', async () => {
    store.messages = [
      { id: 1, isLu: false },
      { id: 2, isLu: false },
    ];
    store.unreadCount = 2;
    messageService.markAllAsRead.mockResolvedValueOnce({
      data: { success: true },
    });

    await store.markAllAsRead();

    expect(store.messages.every((m) => m.isLu)).toBe(true);
    expect(store.unreadCount).toBe(0);
  });

  it('toggleArchive removes message from messages list when filter is inbox', async () => {
    store.activeFilter = 'all';
    store.messages = [
      { id: 1, isArchive: false },
      { id: 2, isArchive: false },
    ];
    messageService.toggleArchive.mockResolvedValueOnce({
      data: { success: true, isArchive: true },
    });

    await store.toggleArchive(1, true);

    expect(store.messages).toHaveLength(1);
    expect(store.messages[0].id).toBe(2);
  });

  it('fetchSentMessages populates sentMessages', async () => {
    const sentList = [{ id: 10, sujet: 'Annonce importante' }];
    messageService.getSentMessages.mockResolvedValueOnce({
      data: { messages: sentList },
    });

    await store.fetchSentMessages();

    expect(store.sentMessages).toEqual(sentList);
  });

  it('sendMessage calls messageService and returns response data', async () => {
    const payload = { sujet: 'Test envoi' };
    messageService.sendMessage.mockResolvedValueOnce({
      data: { success: true, id: 99 },
    });

    const res = await store.sendMessage(payload);

    expect(res).toEqual({ success: true, id: 99 });
  });
});

