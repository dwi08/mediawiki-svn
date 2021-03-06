#ifndef SOCKET_H______
#define SOCKET_H______

#include <sys/socket.h>
#include "FileDescriptor.h"
#include "SocketAddress.h"
#include "Exception.h"

// Socket base class
// Do not instantiate this directly
class Socket : public FileDescriptor
{
protected:
	Socket(int domain, int type, int protocol)
		: FileDescriptor()
	{
		fd = socket(domain, type, protocol);
		if (fd == -1) {
			RaiseError("Socket constructor");
		} else {
			good = true;
		}
	}

public:

	int Connect(SocketAddress & s) {
		if (connect(fd, s.GetBinaryData(), s.GetBinaryLength()) < 0) {
			RaiseError("Socket::Connect");
			return errno;
		} else {
			return 0;
		}
	}

	int Shutdown(int how = 2) {
		if (shutdown(fd, how) < 0) {
			RaiseError("Socket::Shutdown");
			return errno;
		} else {
			return 0;
		}
	}

	// TODO
	// SocketAddress & Accept();

	int Bind(SocketAddress & s) {
		if (bind(fd, s.GetBinaryData(), s.GetBinaryLength()) < 0) {
			RaiseError("Socket::Bind");
			return errno;
		} else {
			return 0;
		}
	}

	boost::shared_ptr<SocketAddress> GetPeerAddress() {
		if (connect(fd, SocketAddress::GetBuffer(), SocketAddress::GetBufferLength()) < 0) {
			RaiseError("Socket::GetPeerAddress");
			peer = boost::shared_ptr<SocketAddress>((SocketAddress*)NULL);
		} else {
			peer = boost::shared_ptr<SocketAddress>(SocketAddress::NewFromBuffer());
		}
		return peer;
	}

	int Listen(unsigned int backlog = 50) {
		if (listen(fd, backlog) < 0) {
			RaiseError("Socket::Listen");
			return errno;
		} else {
			return 0;
		}
	}

	ssize_t Send(void * buf, size_t len, int flags = 0) {
		ssize_t length = send(fd, buf, len, flags);
		if (length == (ssize_t)-1) {
			RaiseError("Socket::Send");
		}
		return length;
	}

	ssize_t SendTo(void * buf, size_t len, SocketAddress & to, int flags = 0) {
		ssize_t length = sendto(fd, buf, len, flags, to.GetBinaryData(), to.GetBinaryLength());
		if (length == (ssize_t)-1) {
			RaiseError("Socket::SendTo");
		}
		return length;
	}

	ssize_t Recv(void *buf, size_t len, int flags = 0) {
		ssize_t length = recv(fd, buf, len, flags);
		if (length == (ssize_t)-1) {
			RaiseError("Socket::Recv");
		}
		return length;
	}

	ssize_t RecvFrom(void *buf, size_t len, boost::shared_ptr<SocketAddress> & to, int flags = 0) {
		socklen_t addrLength = SocketAddress::GetBufferLength();
		ssize_t length = recvfrom(fd, buf, len, flags, SocketAddress::GetBuffer(), &addrLength);
		if (length == (ssize_t)-1) {
			RaiseError("Socket::RecvFrom");
		} else {
			to = SocketAddress::NewFromBuffer();
		}
		return length;
	}

	int SetSockOpt(int level, int optName, const void * optValue, socklen_t optLength) {
		int result = setsockopt(fd, level, optName, optValue, optLength);
		if (result == -1) {
			RaiseError("Socket::SetSockOpt");
			return errno;
		}
		return result;
	}

	int SetSockOpt(int level, int optName, bool optValue) {
		return SetSockOpt(level, optName, (int)optValue);
	}

	int SetSockOpt(int level, int optName, int optValue) {
		return SetSockOpt(level, optName, &optValue, sizeof(int));
	}

	int SetSockOpt(int level, int optName, const std::string & optValue) {
		return SetSockOpt(level, optName, optValue.data(), optValue.size());
	}
protected:
	boost::shared_ptr<SocketAddress> peer;
};

class UDPSocket : public Socket
{
public:
	UDPSocket(int domain = PF_INET)
		: Socket(domain, SOCK_DGRAM, 0) {}
	
	UDPSocket(IPAddress & addr, int port) 
		: Socket(addr.GetDomain(), SOCK_DGRAM, 0)
	{
		boost::shared_ptr<SocketAddress> saddr = addr.NewSocketAddress(port);
		if (Connect(*saddr)) {
			good = false;
		}
	}
	
	int JoinMulticast(const IPAddress & addr);
};

#endif
